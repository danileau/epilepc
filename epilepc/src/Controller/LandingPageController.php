<?php


namespace App\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class LandingPageController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index(){
        return $this->render('landing/index.html.twig', []);
    }

}