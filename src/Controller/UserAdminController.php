<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\UserAdminType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @IsGranted("ROLE_ADMIN")
 * Benutzer dürfen nur Administratoren pflegen
 */
class UserAdminController extends AbstractController
{

    /**
     * @Route("/admin")
     * isualisiert die Benutzerübersicht
     */
    public function index(UserRepository $userRepository){
        return $this->render('user_admin/index.html.twig', [
            'users' => $userRepository->findAll()
        ]);
    }

    /**
     * @Route("/admin/user/new")
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

            return $this->redirectToRoute('app_useradmin_index');
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
        return $this->redirectToRoute('app_useradmin_index');
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
        return $this->redirectToRoute('app_useradmin_index');
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
        return $this->redirectToRoute('app_useradmin_index');
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
        return $this->redirectToRoute('app_useradmin_index');
    }
}