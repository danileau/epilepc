<?php

namespace App\Controller;

use App\Entity\Diaryentry;
use App\Form\DiaryentryType;
use App\Repository\DiaryentryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

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
            'diaryentries' => $diaryentryRepository->findAllFromUser($user->getId()),
        ]);
    }

    /**
     * @Route("/new", name="diaryentry_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $diaryentry = new Diaryentry();
        $form = $this->createForm(DiaryentryType::class, $diaryentry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($diaryentry);
            $entityManager->flush();

            return $this->redirectToRoute('diaryentry_index');
        }

        return $this->render('diaryentry/new.html.twig', [
            'diaryentry' => $diaryentry,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="diaryentry_show", methods={"GET"})
     */
    public function show(Diaryentry $diaryentry): Response
    {

        $user = $this->getUser();
        if ($user->getId() != $diaryentry->getUser()->getId()) {
            throw new AccessDeniedException('Zugriff verweigert');
        }

        return $this->render('diaryentry/show.html.twig', [
            'diaryentry' => $diaryentry,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="diaryentry_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Diaryentry $diaryentry): Response
    {
        $user = $this->getUser();
        if ($user->getId() != $diaryentry->getUser()->getId()) {
            throw new AccessDeniedException('Zugriff verweigert');
        }

        $form = $this->createForm(DiaryentryType::class, $diaryentry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('diaryentry_index', [
                'id' => $diaryentry->getId(),
            ]);
        }

        return $this->render('diaryentry/edit.html.twig', [
            'diaryentry' => $diaryentry,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="diaryentry_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Diaryentry $diaryentry): Response
    {
        $user = $this->getUser();
        if ($user->getId() != $diaryentry->getUser()->getId()) {
            throw new AccessDeniedException('Zugriff verweigert');
        }

        if ($this->isCsrfTokenValid('delete'.$diaryentry->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($diaryentry);
            $entityManager->flush();
        }

        return $this->redirectToRoute('diaryentry_index');
    }
}