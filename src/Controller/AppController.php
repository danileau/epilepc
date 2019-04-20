<?php


namespace App\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AppController extends BaseController
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





}