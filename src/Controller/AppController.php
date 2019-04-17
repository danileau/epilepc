<?php


namespace App\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AppController extends AbstractController
{
    /**
     * @Route("/app", name="app_dashboard")
     * @IsGranted("ROLE_USER")
     */
    public function index(){
        return $this->render('app/dashboard.html.twig', []);
    }

    /**
     * @Route("/app/seizure", name="app_seizure_overview")
     * @IsGranted("ROLE_USER")
     */
    public function seizure_overview(){
        return $this->render('app/seizure.html.twig', []);
    }


    // TODO: In Security Controller verschieben und in / statt /app ablegen
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