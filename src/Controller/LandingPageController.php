<?php


namespace App\Controller;


use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class LandingPageController extends AbstractController
{
    /**
     * @Route("/", name="app_landingpage")
     */
    public function index(){
        return $this->render('landing/index.html.twig', []);
    }

    /**
     * @Route("/detail", name="app_landingpage_detail")
     */
    public function detail(){
        return $this->render('landing/detail.html.twig', []);
    }

    /**
     * @Route("/functions", name="app_landingpage_functions")
     */
    public function functions(){
        return $this->render('landing/fuctions.html.twig', []);
    }

    /**
     * @Route("/security", name="app_landingpage_security")
     */
    public function security(){
        return $this->render('landing/security.html.twig', []);
    }

}