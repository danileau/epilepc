<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordChangeType;
use App\Form\ProfileFormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;


//Schränkt den Zugriff auf alle Routen in diesem Controller ein.
// Die Rolle "ROLE_USER" wird benötigt
/**
 * @IsGranted("ROLE_USER")
 */

class AccountController extends AbstractController
{

    /**
     * @Route("/app/account", name="app_account")
     * Profilübersicht
     */
    public function index(Request $request, UserInterface $user)
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

            $this->addFlash('success', 'Profil erfolgreich bearbeitet!');
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
    public function change_user_password(Request $request, UserPasswordEncoderInterface $passwordEncoder){
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
                    $form->addError(new FormError('Die neuen Passwörter stimmen nicht überein'));
                }else {
                    $newEncodedPassword = $passwordEncoder->encodePassword($user, $new_pwd);
                    $user->setPassword($newEncodedPassword);

                    $em->persist($user);
                    $em->flush();

                    $this->addFlash('success', 'Passwort erfolgreich geändert!');

                    return $this->redirectToRoute('app_account');
                }
            } else {
                $form->addError(new FormError('Passwort nicht korrekt'));
            }
        }

        return $this->render('app/authentication/changePw.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
