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
     * @Route("/api/value_sum", name="api_value_sum")
     */
    public function valueSumApi()
    {

        //Todo
        $user = $this->getUser();
        return $this->json($user, 200, [], [
            'groups' => ['main']
        ]);
    }





}