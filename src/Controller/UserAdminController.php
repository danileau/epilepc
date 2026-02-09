<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\UserAdminType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Benutzer dürfen nur durch Administratoren visualisiert und gepflegt werden
 */
#[IsGranted('ROLE_ADMIN')]
class UserAdminController extends AbstractController
{

    /**
     * Visualisiert die Benutzerübersicht
     */
    #[Route('/admin', name: 'admin_user_index')]   
    public function index(UserRepository $userRepository){

        return $this->render('user_admin/index.html.twig', [
            'users' => $userRepository->findAll()
        ]);

    }

    /**
     * Neuer Benutzer erstellen
     */
    #[Route('/admin/user/new', name: 'admin_user_new')]   
    public function new(EntityManagerInterface $em, Request $request, UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator)
    {
        $form = $this->createForm(UserAdminType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var User $user */
            $user = $form->getData();
            $user->setPassword($passwordEncoder->encodePassword($user, $user->getPassword()));
            $user->setRoles([]);
            $user->setDeactivated(0);
            $user->agreeTerms();
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', $translator->trans("Neuer Benutzer wurde erstellt"));

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('user_admin/new.html.twig', [
            'userForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/user/{id}", name="admin_user_show")
     * Benutzer anzeigen
     */
    #[Route('/admin/user/new', name: 'admin_user_new')]   
    public function show(User $user){
        return $this->render('user_admin/show.html.twig', [
            'user' => $user,
        ]);

    }
    /**
     * @Route("/admin/user/{id}/edit", name="admin_user_edit")
     * Benutzer editieren
     */
    #[Route('/admin/user/new', name: 'admin_user_new')]   
    public function edit(Request $request, User $user, UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(UserAdminType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('Benutzer erfolgreich bearbeitet!'));

            return $this->redirectToRoute('admin_user_index', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('user_admin/edit.html.twig', [
            'user' => $user,
            'userForm' => $form->createView(),
        ]);

    }

    /**
     * @Route("/admin/user/{id}/makeAdmin", name="admin_user_make_admin")
     * Rolle "ROLE_ADMIN" dem Benutzer zuteilen
     */
    #[Route('/admin/user/new', name: 'admin_user_new')]   
    public function makeAdmin(Request $request, User $user, TranslatorInterface $translator){
        //$userRepository->makeAdmin($user);
        $this->addFlash('success', $translator->trans("Adminrechte wurden vergeben"));
        $roles = ["ROLE_ADMIN"];
        $user->setRoles($roles);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * @Route("/admin/user/{id}/removeAdmin", name="admin_user_remove_admin")
     * Rolle "ROLE_ADMIN" entfernen
     */
    #[Route('/admin/user/new', name: 'admin_user_new')]   
    public function removeAdmin(Request $request, User $user, TranslatorInterface $translator){
        //$userRepository->makeAdmin($user);
        $this->addFlash('success', $translator->trans("Adminrechte wurden entfernt"));
        $roles = [];
        $user->setRoles($roles);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * @Route("/admin/user/{id}/makeDeactivated", name="admin_user_make_deactivated")
     * Benutzer deaktivieren
     */
    #[Route('/admin/user/new', name: 'admin_user_new')]   
    public function makeDeactivated(Request $request, User $user, TranslatorInterface $translator){

        $this->addFlash('success', $translator->trans("Benutzer wurde deaktiviert"));
        $user->setDeactivated(1);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Benutzer aktivieren
     */
    #[Route('/admin/user/{id}/removeDeactivated', name: 'admin_user_remove_deactivated')]   
    public function removeDeactivated(Request $request, User $user, TranslatorInterface $translator){

        $this->addFlash('success', $translator->trans("Benutzer wurde reaktiviert"));
        $user->setDeactivated(0);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->redirectToRoute('admin_user_index');
    }
}
