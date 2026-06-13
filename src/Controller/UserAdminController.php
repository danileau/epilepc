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
     *
     * The list is paginated server-side: each row decrypts firstname+lastname
     * (Defuse PBKDF2, ~74ms each), so rendering every user at once exceeded the
     * host's 30s execution cap once the user base passed ~200. The overview
     * metrics are counted via SQL over ALL users (cheap, unencrypted columns)
     * so they are not limited to the current page.
     */
    public function index(Request $request, UserRepository $userRepository){

        $perPage = 25;
        $page = max(1, (int) $request->query->get('page', 1));

        $paginator = $userRepository->findPaginated($page, $perPage);
        $total = $userRepository->countAll();
        $totalPages = (int) max(1, ceil($total / $perPage));

        // Clamp out-of-range page requests to the last real page.
        if ($page > $totalPages) {
            $page = $totalPages;
            $paginator = $userRepository->findPaginated($page, $perPage);
        }

        // Activity windows for the ciphra-migration push — distinct users who
        // created/modified any record recently (no last_login column exists).
        $now = new \DateTime();
        $active30 = $userRepository->countActiveSince((clone $now)->modify('-30 days'));
        $active90 = $userRepository->countActiveSince((clone $now)->modify('-90 days'));

        return $this->render('user_admin/index.html.twig', [
            'users' => $paginator,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'total_count' => $total,
            'admin_count' => $userRepository->countAdmins(),
            'deactivated_count' => $userRepository->countDeactivated(),
            'migrated_count' => $userRepository->countMigrated(),
            'active_30_count' => $active30,
            'active_90_count' => $active90,
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
     * @Route("/admin/user/{id}/makeAdmin", name="admin_user_make_admin", methods={"POST"})
     * Rolle "ROLE_ADMIN" dem Benutzer zuteilen
     */
    public function makeAdmin(Request $request, User $user){
        if ($this->isCsrfTokenValid('admin_action'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('success', "Adminrechte wurden vergeben");
            $user->setRoles(["ROLE_ADMIN"]);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
        }
        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * @Route("/admin/user/{id}/removeAdmin", name="admin_user_remove_admin", methods={"POST"})
     * Rolle "ROLE_ADMIN" entfernen
     */
    public function removeAdmin(Request $request, User $user){
        if ($this->isCsrfTokenValid('admin_action'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('success', "Adminrechte wurden entfernt");
            $user->setRoles([]);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
        }
        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * @Route("/admin/user/{id}/makeDeactivated", name="admin_user_make_deactivated", methods={"POST"})
     * Benutzer deaktivieren
     */
    public function makeDeactivated(Request $request, User $user){
        if ($this->isCsrfTokenValid('admin_action'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('success', "Benutzer wurde deaktiviert");
            $user->setDeactivated(1);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
        }
        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * @Route("/admin/user/{id}/removeDeactivated", name="admin_user_remove_deactivated", methods={"POST"})
     * Benutzer aktivieren
     */
    public function removeDeactivated(Request $request, User $user){
        if ($this->isCsrfTokenValid('admin_action'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('success', "Benutzer wurde reaktiviert");
            $user->setDeactivated(0);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
        }
        return $this->redirectToRoute('admin_user_index');
    }
}
