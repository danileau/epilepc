<?php
/**
 * Created by PhpStorm.
 * User: danileau
 * Date: 16.04.2019
 * Time: 17:03
 */

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserAdminController extends AbstractController
{
    /**
     * @Route("/admin/user/new")
     */
    public function new(EntityManagerInterface $em)
    {
        $user = new User();
        $user->setFirstname('Danilo')
            ->setLastname('Licitra')
            ->setEmail('danilo'.rand(5, 100).'@gmail.com')
            ->setPassword('12345')
            ->setDeactivated(false);

        $em->persist($user);
        $em->flush();

        return new Response(sprintf(
            'User ID: #%d für User %s erstellt!',
            $user->getId(),
            $user->getFirstname()
        ));
    }
}