<?php

namespace App\Controller;

use App\Entity\Diaryentry;
use App\Form\DiaryentryType;
use App\Repository\DiaryentryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/app/diaryentry")
 * @IsGranted("ROLE_USER")
 */
class DiaryentryController extends AbstractController
{
    // Übersicht generieren und anzeigen
    /**
     * @Route("/", name="diaryentry_index", methods={"GET"})
     */
    public function index(DiaryentryRepository $diaryentryRepository, UserInterface $user): Response
    {
        /*
         * ->findAllFromUser erstellt ein Query, welches nur die Einträge mit der mitgegebenen User-ID aus der DB ausliest
         * Die Werte werden dann mit diaryentries übergeben und mit dem twig Template gerendert
         */
        return $this->render('app/diaryentry/index.html.twig', [
            'diaryentries' => $diaryentryRepository->findAllFromUser($user->getId()),
        ]);
    }

    // Neuer Tagebucheintrag erstellen
    /**
     * @Route("/new", name="diaryentry_new", methods={"GET","POST"})
     */
    public function new(Request $request, UserInterface $user, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(DiaryentryType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var Diaryentry $diaryentry */
            $diaryentry = $form->getData();

            // Aktueller user und aktuelle Uhrzeit & Datum fix setzen
            $diaryentry->setCreatedAt(new \DateTime());
            $diaryentry->setModifiedAt(new \DateTime());
            $diaryentry->setUser($user);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($diaryentry);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('Tagebucheintrag erfolgreich erstellt!'));
            return $this->redirectToRoute('diaryentry_index');
        }

        return $this->render('app/diaryentry/new.html.twig', [
            'diaryForm' => $form->createView(),
        ]);
    }

    /*
     * Detailansicht bestehender Eintrag
     * "MANAGE" ermöglicht die Ansicht nur für den User, dessen User-ID dem Eintrag angehängt ist, ansonsten 403 Zugriff verweigert
     */
    /**
     * @Route("/{id}", name="diaryentry_show", methods={"GET"})
     * @IsGranted("MANAGE", subject="diaryentry")
     */
    public function show(Diaryentry $diaryentry, TranslatorInterface $translator): Response
    {

        $user = $this->getUser();
        if ($user->getId() != $diaryentry->getUser()->getId()) {
            throw new AccessDeniedException($translator->trans('Zugriff verweigert'));
        }

        return $this->render('app/diaryentry/show.html.twig', [
            'diaryentry' => $diaryentry,
        ]);
    }

    // Bestehender Eintrag bearbeiten
    /**
     * @Route("/{id}/edit", name="diaryentry_edit", methods={"GET","POST"})
     * @IsGranted("MANAGE", subject="diaryentry")
     */
    public function edit(Request $request, Diaryentry $diaryentry, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        // 403-Exception wenn User_Id != Ersteller ID vom Eintrag
        if ($user->getId() != $diaryentry->getUser()->getId()) {
            throw new AccessDeniedException($translator->trans('Zugriff verweigert'));
        }

        $form = $this->createForm(DiaryentryType::class, $diaryentry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $diaryentry = $form->getData();
            $diaryentry->setModifiedAt(new \DateTime());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($diaryentry);
            $entityManager->flush();


            $this->addFlash('success', $translator->trans('Tagebucheintrag erfolgreich bearbeitet!'));
            return $this->redirectToRoute('diaryentry_index', [
                'id' => $diaryentry->getId(),
            ]);
        }

        return $this->render('app/diaryentry/edit.html.twig', [
            'diaryentry' => $diaryentry,
            'diaryForm' => $form->createView(),
        ]);
    }

    // Tagebucheintrag löschen
    /**
     * @Route("/{id}", name="diaryentry_delete", methods={"DELETE"})
     * @IsGranted("MANAGE", subject="diaryentry")
     */
    public function delete(Request $request, Diaryentry $diaryentry, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        if ($user->getId() != $diaryentry->getUser()->getId()) {
            throw new AccessDeniedException($translator->trans('Zugriff verweigert'));
        }

        if ($this->isCsrfTokenValid('delete'.$diaryentry->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($diaryentry);
            $entityManager->flush();
        }

        return $this->redirectToRoute('diaryentry_index');
    }
}
