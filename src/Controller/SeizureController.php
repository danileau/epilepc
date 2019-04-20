<?php

namespace App\Controller;

use App\Entity\Seizure;
use App\Form\SeizureType;
use App\Repository\SeizureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/app/seizure")
 */
class SeizureController extends AbstractController
{
    /**
     * @Route("/", name="seizure_index", methods={"GET"})
     */
    public function index(SeizureRepository $seizureRepository): Response
    {
        return $this->render('seizure/index.html.twig', [
            'seizures' => $seizureRepository->findAllOrderedByTimestamp(),
        ]);
    }

    /**
     * @Route("/new", name="seizure_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $seizure = new Seizure();
        $form = $this->createForm(SeizureType::class, $seizure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($seizure);
            $entityManager->flush();

            return $this->redirectToRoute('seizure_index');
        }

        return $this->render('seizure/new.html.twig', [
            'seizure' => $seizure,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="seizure_show", methods={"GET"})
     */
    public function show(Seizure $seizure): Response
    {
        return $this->render('seizure/show.html.twig', [
            'seizure' => $seizure,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="seizure_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Seizure $seizure): Response
    {
        $form = $this->createForm(SeizureType::class, $seizure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('seizure_index', [
                'id' => $seizure->getId(),
            ]);
        }

        return $this->render('seizure/edit.html.twig', [
            'seizure' => $seizure,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="seizure_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Seizure $seizure): Response
    {
        if ($this->isCsrfTokenValid('delete'.$seizure->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($seizure);
            $entityManager->flush();
        }

        return $this->redirectToRoute('seizure_index');
    }
}
