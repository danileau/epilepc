<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\UserAdminType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @IsGranted("ROLE_ADMIN")
 * Benutzer dürfen nur durch Administratoren gepflegt werden
 */
class UserAdminController extends AbstractController
{

    /**
     * @Route("/admin", name="admin_user_index")
     * Visualisiert die Benutzerübersicht
     */
    public function index(UserRepository $userRepository){

        set_time_limit(300);
        #$em = $this->getDoctrine()->getManager();
        #$query = $em->createQuery('SELECT u FROM App\Entity\User u')
        #    ->setCacheable(true)
        #    ->setCacheMode('NORMAL');
    
        #$users = $query->getResult();
        
        return $this->render('user_admin/index.html.twig', [
            'users' => $userRepository->findAll()
        #    'users' => $users
        ]);

    }

    /**
     * @Route("/admin/user/new", name="admin_user_new")
     * Neuer Benutzer erstellen
     */
    public function new(EntityManagerInterface $em, Request $request, UserPasswordEncoderInterface $passwordEncoder)
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

            $this->addFlash('success', "Neuer Benutzer wurde erstellt");

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
    public function show(User $user){
        return $this->render('user_admin/show.html.twig', [
            'user' => $user,
        ]);

    }
    /**
     * @Route("/admin/user/{id}/edit", name="admin_user_edit")
     * Benutzer editieren
     */
    public function edit(Request $request, User $user, UserPasswordEncoderInterface $passwordEncoder): Response
    {

        $form = $this->createForm(UserAdminType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Benutzer erfolgreich bearbeitet!');

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
    public function makeAdmin(Request $request, User $user){
        //$userRepository->makeAdmin($user);
        $this->addFlash('success', "Adminrechte wurden vergeben");
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
    public function removeAdmin(Request $request, UserRepository $userRepository, User $user){
        //$userRepository->makeAdmin($user);
        $this->addFlash('success', "Adminrechte wurden entfernt");
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
    public function makeDeactivated(Request $request, User $user){

        $this->addFlash('success', "Benutzer wurde deaktiviert");
        $user->setDeactivated(1);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * @Route("/admin/user/{id}/removeDeactivated", name="admin_user_remove_deactivated")
     * Benutzer aktivieren
     */
    public function removeDeactivated(Request $request, UserRepository $userRepository, User $user){

        $this->addFlash('success', "Benutzer wurde reaktiviert");
        $user->setDeactivated(0);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->redirectToRoute('admin_user_index');
    }
}
