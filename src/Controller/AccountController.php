<?php

namespace App\Controller;

use App\Form\PasswordChangeType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @IsGranted("ROLE_USER")
 */

class AccountController extends AbstractController
{
    /**
     * @Route("/app/account", name="app_account")
     */
    public function index()
    {
        return $this->render('app/account/profile.html.twig', [
            'controller_name' => 'AccountController',
        ]);
    }

    /**
     * @Route("/api/account", name="api_account")
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
