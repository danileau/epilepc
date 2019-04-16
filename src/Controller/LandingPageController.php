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
     * @Route("/wieso-epilepc", name="app_landingpage_wieso")
     */
    public function detail(){
        return $this->render('landing/wieso.html.twig', []);
    }

    /**
     * @Route("/funktionen", name="app_landingpage_functions")
     */
    public function functions(){
        return $this->render('landing/function.html.twig', []);
    }


}