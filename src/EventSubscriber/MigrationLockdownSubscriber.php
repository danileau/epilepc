<?php

namespace App\EventSubscriber;

use App\Service\LifecycleService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Blocks state-changing requests when either:
 *   (a) the per-user `migrated_at` flag is set, or
 *   (b) the global lifecycle phase is readonly/decommission.
 *
 * Allow-list (always permitted regardless of either condition):
 *   - safe HTTP methods (GET, HEAD, OPTIONS)
 *   - explicit POST endpoints needed for auth + migration to keep working:
 *       app_login, app_logout, app_change_password,
 *       app_ciphra_migrate_start
 *   - anonymous machine endpoints (ciphra_migration_export, _complete)
 *     never reach the user-bound check anyway
 *
 * Denied requests get a flash + redirect to /app/account.
 */
class MigrationLockdownSubscriber implements EventSubscriberInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    private const ROUTE_ALLOW_LIST = [
        'app_login',
        'app_logout',
        'app_change_password',
        'app_ciphra_migrate_start',
        'app_ciphra_export_json',
        'app_ciphra_export_csv',
        'ciphra_migration_export',
        'ciphra_migration_complete',
    ];

    /**
     * Routes that are write-intent regardless of HTTP method — the GET form
     * to create or edit content must also be blocked, not just the POST.
     * Pattern matches Symfony route names ending in _new / _edit / _create
     * / _update / _delete.
     */
    private const WRITE_INTENT_PATTERN = '/(^|_)(new|edit|create|update|delete)$/i';

    /** @var LifecycleService */
    private $lifecycle;
    /** @var Security */
    private $security;
    /** @var RouterInterface */
    private $router;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        LifecycleService $lifecycle,
        Security $security,
        RouterInterface $router,
        TranslatorInterface $translator
    ) {
        $this->lifecycle = $lifecycle;
        $this->security = $security;
        $this->router = $router;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onController',
        ];
    }

    public function onController(ControllerEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route', '');

        // Block new-account registration the moment we hit `announce`.
        // Once ciphra is announced, it's the only forward path — sending
        // newcomers to epilepc would create accounts that go read-only in
        // weeks. Redirect to /login with a flash that points them to ciphra.
        if ($route === 'app_register' && $this->lifecycle->isRegistrationDisabled()) {
            $session = $request->getSession();
            if ($session !== null) {
                try {
                    $session->getFlashBag()->add(
                        'warning',
                        $this->translator->trans('Auf epilepc können keine neuen Konten erstellt werden. Bitte erstelle dein Konto auf ciphra.ch.')
                    );
                } catch (\Throwable $e) { /* best-effort */ }
            }
            $target = $this->router->generate('app_login');
            $event->setController(function () use ($target) {
                return new RedirectResponse($target);
            });
            return;
        }

        // Determine lock state once, up front.
        $user = $this->security->getUser();
        $userMigrated = is_object($user) && method_exists($user, 'isMigrated') && $user->isMigrated();
        $phaseBlocks = $this->lifecycle->isWritesBlocked();
        $readsBlocked = $this->lifecycle->isReadsBlocked();
        $locked = $userMigrated || $phaseBlocks;

        if (!$locked) {
            return;
        }

        // Locked. Now decide whether this specific request is permitted.

        // Explicit allow-list passes regardless of method.
        if (in_array($route, self::ROUTE_ALLOW_LIST, true)) {
            return;
        }

        // In `decommission`, even GET-reads are blocked — drop the
        // safe-method bypass entirely so the user is funnelled to the
        // forced screen on /app/account. (`readonly` still allows reads.)
        if ($readsBlocked) {
            // fall through to redirect
        } else {
            // Safe methods (GET/HEAD/OPTIONS) pass UNLESS the route name signals
            // write intent (_new, _edit, _delete, etc.) — the GET form for those
            // is just as forbidden as the POST submission. This is what catches
            // direct visits to /app/seizure/new.
            $isWriteIntent = (bool) preg_match(self::WRITE_INTENT_PATTERN, $route);
            if (in_array($request->getMethod(), self::SAFE_METHODS, true) && !$isWriteIntent) {
                return;
            }
        }

        // Block. Set flash + redirect to /app/account.
        $reason = $userMigrated
            ? 'Du hast deine Daten bereits zu ciphra übertragen. epilepc ist für dich im Nur-Lese-Modus.'
            : 'epilepc ist im Nur-Lese-Modus. Schreiboperationen sind deaktiviert.';

        try {
            $session = $request->getSession();
            if ($session !== null) {
                $session->getFlashBag()->add('warning', $this->translator->trans($reason));
            }
        } catch (\Throwable $e) {
            // No session — anonymous POST; flash is best-effort.
        }

        // If the user isn't logged in (anonymous POST), send them to login.
        $target = $user === null
            ? $this->router->generate('app_login')
            : $this->router->generate('app_account');

        $event->setController(function () use ($target) {
            return new RedirectResponse($target);
        });
    }
}
