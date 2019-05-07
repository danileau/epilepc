<?php

namespace App\Controller;

use App\Entity\Seizuretype;
use App\Form\SeizuretypeFormType;
use App\Repository\SeizuretypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/seizuretype")
 * @IsGranted("ROLE_ADMIN")
 * Anfallsarten dürfen nur Administratoren pflegen
 */
class SeizuretypeController extends AbstractController
{
    /**
     * @Route("/", name="seizuretype_index", methods={"GET"})
     * Anfallsarten anzeigen
     */
    public function index(SeizuretypeRepository $seizuretypeRepository): Response
    {
        return $this->render('app/seizuretype/index.html.twig', [
            'seizuretypes' => $seizuretypeRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="seizuretype_new", methods={"GET","POST"})
     * Neue Anfallsart erstellen
     */
    public function new(Request $request): Response
    {
        // Generiert das Formular wie in SeizuretypeFormType definiert.
        // Wenn das Formular ausgefüllt worden ist, wird der Inhalt in die Datenbank geschrieben
        $form = $this->createForm(SeizuretypeFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Seizuretype $seizuretype */
            $seizuretype = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($seizuretype);
            $entityManager->flush();

            $this->addFlash('success', 'Anfallart erfolgreich erstellt!');
            return $this->redirectToRoute('seizuretype_index');
        }

        return $this->render('app/seizuretype/new.html.twig', [
            'seizuretypForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="seizuretype_show", methods={"GET"})
     * Anfallsart anzeigen
     */
    public function show(Request $request, Seizuretype $seizuretype): Response
    {
        $form = $this->createForm(SeizuretypeFormType::class, $seizuretype);
        $form->handleRequest($request);

        return $this->render('app/seizuretype/show.html.twig', [
            'seizuretyp' => $seizuretype,
            'seizuretypForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="seizuretype_edit", methods={"GET","POST"})
     * Anfallsart bearbeiten
     */
    public function edit(Request $request, Seizuretype $seizuretype): Response
    {
        $form = $this->createForm(SeizuretypeFormType::class, $seizuretype);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('seizuretype_index', [
                'id' => $seizuretype->getId(),
            ]);
        }

        return $this->render('app/seizuretype/edit.html.twig', [
            'seizuretype' => $seizuretype,
            'seizuretypForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="seizuretype_delete", methods={"DELETE"})
     * Anfallsart löschen
     */
    public function delete(Request $request, Seizuretype $seizuretype): Response
    {
        if ($this->isCsrfTokenValid('delete'.$seizuretype->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($seizuretype);
            $entityManager->flush();
        }

        return $this->redirectToRoute('seizuretype_index');
    }
}
