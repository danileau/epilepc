<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordChangeType;
use App\Form\ProfileFormType;
use App\Service\EpilepcBundleSerializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * @IsGranted("ROLE_USER")
 * Schränkt den Zugriff auf alle Routen in diesem Controller ein. Die Rolle "ROLE_USER" wird benötigt
 */

class AccountController extends AbstractController
{

    /**
     * @Route("/app/account", name="app_account")
     * Profilübersicht
     */
    public function index(Request $request, UserInterface $user, TranslatorInterface $translator)
    {
        // Erstellt das Formular Profile
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        // Wenn das Formular versendet wurde und valid ist, DB Abfragen durchführen
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var User $userForm */
            $user = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('Profil erfolgreich bearbeitet!'));
            return $this->redirectToRoute('app_account');
        }

        return $this->render('app/account/profile.html.twig', [
            'user' => $user,
            'profileForm' => $form->createView(),
        ]);
    }


    /**
     * @Route("/api/account", name="api_account")
     * Stellt die User Informationen als JSON String zur Verfügung
     */
    public function accountApi()
    {
        $user = $this->getUser();
        return $this->json($user, 200, [], [
            'groups' => ['main']
        ]);
    }


    /**
     * @Route("/app/account/changePassword", name="app_change_password")
     * Passwort ändern
     */
    public function change_user_password(Request $request, UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator){
        $form = $this->createForm(PasswordChangeType::class);
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $old_pwd = $data["old_password"];
            $new_pwd = $data["new_password"];
            $new_pwd_confirm = $data["new_password_confirm"];

            if ($passwordEncoder->isPasswordValid($user, $old_pwd)) {
                if ($new_pwd != $new_pwd_confirm){
                    $form->addError(new FormError($translator->trans('Die neuen Passwörter stimmen nicht überein')));
                }else {
                    $newEncodedPassword = $passwordEncoder->encodePassword($user, $new_pwd);
                    $user->setPassword($newEncodedPassword);

                    $em->persist($user);
                    $em->flush();

                    $this->addFlash('success', $translator->trans('Passwort erfolgreich geändert!'));

                    return $this->redirectToRoute('app_account');
                }
            } else {
                $form->addError(new FormError($translator->trans('Passwort nicht korrekt')));
            }
        }

        return $this->render('app/authentication/changePw.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/app/account/export.json", name="app_ciphra_export_json", methods={"GET"})
     *
     * Self-serve JSON export of the user's epilepc data. Byte-identical to
     * the bundle the migration endpoint emits (schema v1.1), so the same
     * file can be re-imported elsewhere later if a compatible tool exists.
     *
     * Always available, regardless of lifecycle phase or per-user migration
     * state — the user's data must stay accessible for download. The
     * MigrationLockdownSubscriber explicitly allow-lists this route.
     */
    public function exportJson(EpilepcBundleSerializer $serializer): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $now = new \DateTimeImmutable();
        $bundle = $serializer->serialize($user, $now);

        $response = new JsonResponse($bundle, 200, [], false);
        $response->headers->set(
            'Content-Disposition',
            sprintf(
                'attachment; filename="epilepc-export-%s-%s.json"',
                preg_replace('/[^a-z0-9._-]/i', '_', $user->getEmail()),
                $now->format('Y-m-d')
            )
        );
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }

    /**
     * @Route("/app/account/export.csv", name="app_ciphra_export_csv", methods={"GET"})
     *
     * CSV export of the user's data — flat single file with a `type` column
     * (seizure | event | medication | diary). Wide format with empty cells
     * where the type doesn't have a given field. Opens directly in Excel /
     * LibreOffice / Numbers without unzipping.
     *
     * Same access policy as the JSON export — allow-listed in the subscriber.
     */
    public function exportCsv(EpilepcBundleSerializer $serializer): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $now = new \DateTimeImmutable();
        $bundle = $serializer->serialize($user, $now);

        $columns = ['type', 'id', 'date', 'time', 'type_name', 'title', 'name', 'text', 'notes', 'dose', 'as_needed', 'started_at', 'ended_at'];

        $fh = fopen('php://temp', 'w+');
        // UTF-8 BOM — coaxes Excel into reading "Anfälle" / "Tagebuch" etc.
        // as UTF-8 instead of misinterpreting as Windows-1252.
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, $columns);

        foreach ($bundle['seizures'] as $s) {
            fputcsv($fh, [
                'seizure',
                $s['epilepc_id'],
                $s['date'],
                $s['time'] ?? '',
                $s['type_name'] ?? '',
                '', '', '',
                $s['notes'] ?? '',
                '', '', '', '',
            ]);
        }
        foreach ($bundle['events'] as $e) {
            fputcsv($fh, [
                'event',
                $e['epilepc_id'],
                $e['date'],
                '', '',
                $e['title'],
                '', '',
                $e['notes'],
                '', '', '', '',
            ]);
        }
        foreach ($bundle['medications'] as $m) {
            fputcsv($fh, [
                'medication',
                $m['epilepc_id'],
                '', '', '', '',
                $m['name'],
                '',
                $m['notes'] ?? '',
                $m['dose'] ?? '',
                $m['as_needed'] ? '1' : '0',
                $m['started_at'] ?? '',
                $m['ended_at'] ?? '',
            ]);
        }
        foreach ($bundle['diary'] as $d) {
            fputcsv($fh, [
                'diary',
                $d['epilepc_id'],
                $d['date'],
                $d['time'] ?? '',
                '', '', '',
                $d['text'],
                '', '', '', '', '',
            ]);
        }

        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        $response = new Response($csv, 200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set(
            'Content-Disposition',
            sprintf(
                'attachment; filename="epilepc-export-%s-%s.csv"',
                preg_replace('/[^a-z0-9._-]/i', '_', $user->getEmail()),
                $now->format('Y-m-d')
            )
        );
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }
}
