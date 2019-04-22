<?php

namespace App\Controller;

use App\Repository\DiaryentryRepository;
use App\Entity\User;
use App\Entity\Diaryentry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/app/diaryentry")
 */
class DiaryentryController extends AbstractController
{
    /**
     * @Route("/", name="diaryentry_index", methods={"GET"})
     */
    public function index(DiaryentryRepository $diaryentryRepository, UserInterface $user): Response
    {
        return $this->render('diaryentry/index.html.twig', [
            'diaryentries' => $diaryentryRepository->findAllFromUser($user->getId())
        ]);
    }

}
