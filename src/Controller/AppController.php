<?php


namespace App\Controller;


use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AppController extends AbstractController
{
    /**
     * @Route("/app", name="app_landingpage")
     */
    public function index(){
        return $this->render('landing/index.html.twig', []);
    }

    /**
     * @Route("/app/login", name="app_login")
     */
    public function login(){
        return $this->render('app/authentication/login.html.twig', []);
    }

    /**
     * @Route("/app/register", name="app_register")
     */
    public function register(){
        return $this->render('app/authentication/register.html.twig', []);
    }

    /**
     * @Route("/app/forgot", name="app_forgot")
     */
    public function forgot(){
        return $this->render('app/authentication/forgot.html.twig', []);
    }



}