<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('app/authentication/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(){
        return $this->render('app/authentication/register.html.twig', []);
    }

    /**
     * @Route("/password-forgot", name="app_forgot")
     */
    public function forgot(){
        return $this->render('app/authentication/forgot.html.twig', []);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
    }

}
