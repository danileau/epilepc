<?php

namespace App\Controller;

use App\Entity\Seizure;
use App\Entity\User;
use App\Form\SeizureFormType;
use App\Repository\SeizureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/app/seizure")
 */
class SeizureController extends AbstractController
{
    /**
     * @Route("/", name="seizure_index", methods={"GET"})
     */
    public function index(SeizureRepository $seizureRepository, UserInterface $user): Response
    {

        return $this->render('app/seizure/index.html.twig', [
            'seizures' => $seizureRepository->findAllFromUser($user->getId())
        ]);

    }

    // Todo: Felder encrypten beim schreiben und Auslesen

    /**
     * @Route("/new", name="seizure_new", methods={"GET","POST"})
     */
    public function new(Request $request, UserInterface $user): Response
    {
        $form = $this->createForm(SeizureFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var Seizure $seizure */
            $seizure = $form->getData();

            $seizure->setCreatedAt(new \DateTime());
            $seizure->setModifiedAt(new \DateTime());
            $seizure->setUser($user);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($seizure);
            $entityManager->flush();

            $this->addFlash('success', 'Anfall erfolgreich erstellt!');
            return $this->redirectToRoute('seizure_index');
        }

        return $this->render('app/seizure/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="seizure_show", methods={"GET"})
     * @IsGranted("MANAGE", subject="seizure")
     */
    public function show(Seizure $seizure): Response
    {

        return $this->render('app/seizure/show.html.twig', [
            'seizure' => $seizure,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="seizure_edit", methods={"GET","POST"})
     * @IsGranted("MANAGE", subject="seizure")
     */
    public function edit(Request $request, Seizure $seizure, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SeizureFormType::class, $seizure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($seizure);
            $entityManager->flush();

            $this->addFlash('success', 'Anfall erfolgreich bearbeitet!');

            return $this->redirectToRoute('seizure_edit', [
                'id' => $seizure->getId(),
            ]);
        }

        return $this->render('app/seizure/edit.html.twig', [
            'seizure' => $seizure,
            'user' => $this->getUser(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="seizure_delete", methods={"DELETE"})
     * @IsGranted("MANAGE", subject="seizure")
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
