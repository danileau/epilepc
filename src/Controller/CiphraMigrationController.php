<?php

namespace App\Controller;

use App\Entity\MigrationToken;
use App\Repository\MigrationTokenRepository;
use App\Service\CiphraExportRateLimiter;
use App\Service\EpilepcBundleSerializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Two endpoints driving the epilepc → ciphra migration:
 *
 *   POST  /{_locale}/app/account/migrate/start
 *     ROLE_USER + CSRF. Mints (or hands back) a single-use, 7-day-valid
 *     MigrationToken and returns the link the user should open in ciphra.
 *
 *   GET   /api/ciphra-export/{token}            ← declared in config/routes.yaml
 *     Anonymous, CORS for ciphra.ch only. One-shot bundle export.
 *
 * The export route is locale-free by design — declared in config/routes.yaml
 * to bypass the /{_locale} prefix the annotation loader applies. Ciphra's
 * fetcher hits `https://epilepc.ch/api/ciphra-export/<token>` with no locale.
 */
class CiphraMigrationController extends AbstractController
{
    /**
     * @Route("/app/account/migrate/start", name="app_ciphra_migrate_start", methods={"POST"})
     * @IsGranted("ROLE_USER")
     */
    public function start(Request $request, MigrationTokenRepository $tokens): Response
    {
        $submittedToken = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('migrate_start', $submittedToken)) {
            $this->addFlash('error', 'Ungültige Anfrage. Bitte erneut versuchen.');
            return $this->redirectToRoute('app_account');
        }

        $user = $this->getUser();
        $now = new \DateTimeImmutable();

        // Re-use an existing unused, unexpired token if one is in flight —
        // avoids minting parallel tokens when the user clicks twice or
        // returns to the page after a previous attempt.
        $token = $tokens->findActiveForUser($user, $now);

        if ($token === null) {
            $token = new MigrationToken();
            $token->setUser($user);
            $token->setToken($this->generateUrlSafeToken());
            $token->setCreatedAt($now);
            $token->setExpiresAt($now->modify('+7 days'));
            $em = $this->getDoctrine()->getManager();
            $em->persist($token);
            $em->flush();
        }

        $ciphraOrigin = (string) ($_ENV['CIPHRA_ORIGIN'] ?? 'https://ciphra.ch');
        $sourceHost   = (string) ($_ENV['EPILEPC_ORIGIN'] ?? 'epilepc.ch');

        // Fragment-encoded so the token never appears in the ciphra server
        // logs. The browser keeps it client-side only.
        $url = sprintf(
            '%s/migrate#migrate=%s&source=%s',
            rtrim($ciphraOrigin, '/'),
            $token->getToken(),
            $sourceHost
        );

        return $this->render('app/account/migrate_link.html.twig', [
            'url'        => $url,
            'expires_at' => $token->getExpiresAt(),
        ]);
    }

    /**
     * GET /api/ciphra-export/{token} — public, anonymous, single-use.
     * Route bound in config/routes.yaml so it bypasses the /{_locale} prefix.
     */
    public function export(
        Request $request,
        string $token,
        MigrationTokenRepository $tokens,
        EpilepcBundleSerializer $serializer,
        CiphraExportRateLimiter $rateLimiter
    ): Response {
        $corsHeaders = $this->corsHeaders($request);

        if ($request->getMethod() === 'OPTIONS') {
            return new Response('', 204, $corsHeaders);
        }

        $now = new \DateTimeImmutable();

        // Rate-limit before any token DB read so a scripted enumerator
        // can't probe the token space for free. Records the attempt either way.
        $retryAfter = $rateLimiter->checkAndRecord((string) $request->getClientIp(), $now);
        if ($retryAfter !== null) {
            $response = $this->jsonWithCors(
                ['error' => 'rate_limited'],
                429,
                $corsHeaders
            );
            $response->headers->set('Retry-After', (string) $retryAfter);
            return $response;
        }

        $entity = $tokens->findOneBy(['token' => $token]);

        if ($entity === null) {
            return $this->jsonWithCors(['error' => 'unknown_token'], 404, $corsHeaders);
        }

        if ($entity->isUsed()) {
            return $this->jsonWithCors(['error' => 'token_already_used'], 410, $corsHeaders);
        }
        if ($entity->isExpired($now)) {
            return $this->jsonWithCors(['error' => 'token_expired'], 401, $corsHeaders);
        }

        // Atomic single-use stamp BEFORE serialising. If the export itself
        // throws, the token is still consumed — by design. A retry would
        // need a new token, which is the safer default.
        $entity->setUsedAt($now);
        if ($entity->getIpFirstSeen() === null) {
            $entity->setIpFirstSeen((string) $request->getClientIp());
        }
        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $bundle = $serializer->serialize($entity->getUser(), $now);

        return $this->jsonWithCors($bundle, 200, $corsHeaders);
    }

    /**
     * @return array<string,string>
     */
    private function corsHeaders(Request $request): array
    {
        $allowed = [
            (string) ($_ENV['CIPHRA_ORIGIN'] ?? 'https://ciphra.ch'),
            'http://localhost:5173',
            'http://localhost:8080',
            'http://127.0.0.1:5173',
        ];
        $origin = (string) $request->headers->get('Origin', '');
        $headers = [
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
            'Cache-Control'                => 'no-store',
            'Vary'                         => 'Origin',
        ];
        if (in_array($origin, $allowed, true)) {
            $headers['Access-Control-Allow-Origin'] = $origin;
        } else {
            // No origin echo — preflight will fail, fetcher will see CORS
            // error. Browsers won't deliver the response body.
            $headers['Access-Control-Allow-Origin'] = $allowed[0];
        }
        return $headers;
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,string> $headers
     */
    private function jsonWithCors(array $payload, int $status, array $headers): JsonResponse
    {
        $response = new JsonResponse($payload, $status);
        foreach ($headers as $k => $v) {
            $response->headers->set($k, $v);
        }
        return $response;
    }

    private function generateUrlSafeToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * POST /api/migration-complete/{token} — ciphra signals epilepc that the
     * client-side import has succeeded. From this moment on, the token's
     * user is in read+export-only mode (`User.migrated_at` is stamped).
     *
     * Route bound in config/routes.yaml so it bypasses the /{_locale} prefix.
     *
     * Anonymous (the token itself is the auth). Idempotent — a second call
     * with the same token returns 200 without re-stamping. Validates that
     * the token's bundle was actually fetched (`used_at IS NOT NULL`) before
     * stamping, so a stolen-but-never-fetched token can't lock a user out.
     */
    public function complete(
        Request $request,
        string $token,
        MigrationTokenRepository $tokens,
        CiphraExportRateLimiter $rateLimiter
    ): Response {
        $corsHeaders = $this->corsHeaders($request);

        if ($request->getMethod() === 'OPTIONS') {
            return new Response('', 204, $corsHeaders);
        }

        $now = new \DateTimeImmutable();

        $retryAfter = $rateLimiter->checkAndRecord((string) $request->getClientIp(), $now);
        if ($retryAfter !== null) {
            $response = $this->jsonWithCors(['error' => 'rate_limited'], 429, $corsHeaders);
            $response->headers->set('Retry-After', (string) $retryAfter);
            return $response;
        }

        $entity = $tokens->findOneBy(['token' => $token]);
        if ($entity === null) {
            return $this->jsonWithCors(['error' => 'unknown_token'], 404, $corsHeaders);
        }

        // Bundle must have been fetched before completion can be signalled.
        // Prevents an attacker with a leaked-but-unused token from locking
        // a user out before they've actually migrated anything.
        if (!$entity->isUsed()) {
            return $this->jsonWithCors(['error' => 'token_not_fetched'], 409, $corsHeaders);
        }

        // Idempotent. Second call: just return success.
        if ($entity->isMigrationCompleted()) {
            return $this->jsonWithCors(['ok' => true, 'idempotent' => true], 200, $corsHeaders);
        }

        $user = $entity->getUser();
        if ($user === null) {
            return $this->jsonWithCors(['error' => 'orphan_token'], 409, $corsHeaders);
        }

        $entity->setMigrationCompletedAt($now);
        if (!$user->isMigrated()) {
            $user->setMigratedAt($now);
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->jsonWithCors(['ok' => true], 200, $corsHeaders);
    }
}
