<?php

namespace App\Controller;

use App\Entity\Medication;
use App\Entity\User;
use App\Form\MedicationType;
use App\Repository\MedicationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/app/medication')]
#[IsGranted('ROLE_USER')]
class MedicationController extends AbstractController
{

    /**
     * Übersicht generieren und anzeigen
     */
    //#[Route('/', name:'medication_index', methods: ['GET'])]    
    //public function index(MedicationRepository $medicationRepository, UserInterface $user): Response
    //{
    //    return $this->render('app/medication/index.html.twig', [
    //        'medications' => $medicationRepository->findAllFromUser($user->getId())
    //    ]);
    //}


    #[Route('/', name: 'medication_index', methods: ['GET'])]
    public function index(
        Request $request,
        MedicationRepository $medicationRepository,
        UserInterface $user
    ): Response {
        // 1. Figure out the current page from the query string
        $page = $request->query->getInt('page', 1);
        $limit = 15; // how many rows per page
    
        // 2. Get the paginated results
        $paginator = $medicationRepository->paginateByUser($user->getId(), $page, $limit);
    
        // 3. (Optional) Count total items to build page navigation
        $totalItems = count($paginator);
        $totalPages = ceil($totalItems / $limit);
    
        return $this->render('app/medication/index.html.twig', [
            'medications' => $paginator,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
        ]);
    }    

    /**
     * Neue Medikation erstellen
     */
    #[Route('/new', name:'medication_new', methods: ['GET', 'POST'])]    
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
     * Bestehende Medikation anzeigen - eingeschränkt auf Owner
     */
    #[Route('/{id}', name:'medication_show', methods: ['GET'])]    
    #[IsGranted('MANAGE', subject: 'medication')]    
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
     * Bestehende Medikation bearbeiten - Eingeschränkt auf Owner
     */
    #[Route('/{id}/edit', name:'medication_edit', methods: ['GET', 'POST'])]    
    #[IsGranted('MANAGE', subject: 'medication')]    
    public function edit(Request $request, Medication $medication, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(MedicationType::class, $medication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $medication->setModifiedAt(new \DateTime());
            $em->persist($medication);
            $em->flush();

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
     * Bestehende Medikation löschen - Eingeschränkt auf Owner
     */
    #[Route('/{id}', name:'medication_delete', methods: ['DELETE'])]    
    #[IsGranted('MANAGE', subject: 'medication')]    
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
