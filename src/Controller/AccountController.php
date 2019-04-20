<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

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
}
