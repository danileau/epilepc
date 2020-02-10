<?php

namespace App\Controller;

use App\Entity\Medication;
use App\Form\MedicationType;
use App\Repository\MedicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/app/medication")
 * @IsGranted("ROLE_USER")
 */
class MedicationController extends AbstractController
{

    /**
     * @Route("/", name="medication_index", methods={"GET"})
     * Übersicht generieren und anzeigen
     */
    public function index(MedicationRepository $medicationRepository, UserInterface $user): Response
    {
        return $this->render('app/medication/index.html.twig', [
            'medications' => $medicationRepository->findAllFromUser($user->getId())
        ]);
    }


    /**
     * @Route("/new", name="medication_new", methods={"GET","POST"})
     * Neue Medikation erstellen
     */
    public function new(Request $request, UserInterface $user, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(MedicationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var Medication $medication */
            $medication = $form->getData();

            $medication->setCreatedAt(new \DateTime());
            $medication->setModifiedAt(new \DateTime());
            $medication->setUser($user);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($medication);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('Medikation erfolgreich erstellt!'));
            return $this->redirectToRoute('medication_index');
        }

        return $this->render('app/medication/new.html.twig', [
            'medicationForm' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}", name="medication_show", methods={"GET"})
     * @IsGranted("MANAGE", subject="medication")
     * Bestehende Medikation anzeigen - eingeschränkt auf Owner
     */
    public function show(Request $request, Medication $medication): Response
    {
        $form = $this->createForm(MedicationType::class, $medication);
        $form->handleRequest($request);

        return $this->render('app/medication/show.html.twig', [
            'medication' => $medication,
            'medicationForm' => $form->createView()
        ]);
    }


    /**
     * @Route("/{id}/edit", name="medication_edit", methods={"GET","POST"})
     * @IsGranted("MANAGE", subject="medication")
     * Bestehende Medikation bearbeiten - Eingeschränkt auf Owner
     */
    public function edit(Request $request, Medication $medication, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(MedicationType::class, $medication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($medication);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('Medikation erfolgreich bearbeitet!'));

            return $this->redirectToRoute('medication_edit', [
                'id' => $medication->getId(),
            ]);
        }

        return $this->render('app/medication/edit.html.twig', [
            'medication' => $medication,
            'user' => $this->getUser(),
            'medicationForm' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}", name="medication_delete", methods={"DELETE"})
     * @IsGranted("MANAGE", subject="medication")
     * Bestehende Medikation löschen - Eingeschränkt auf Owner
     */
    public function delete(Request $request, Medication $medication): Response
    {
        if ($this->isCsrfTokenValid('delete'.$medication->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($medication);
            $entityManager->flush();
        }

        return $this->redirectToRoute('medication_index');
    }
}
