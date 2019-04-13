<?php


namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class SeizureController
{
    /**
     * @Route("/")
     */
    public function index(){
        return new Response('First indexpage');
    }

    /**
     * @Route("/seizure/{slug}")
     */
    public function show($slug) {
        return new Response(sprintf(
            'Future page to show the seizure: %s',
            $slug)
        );
    }

}