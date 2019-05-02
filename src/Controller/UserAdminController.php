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
 */
class UserAdminController extends AbstractController
{
    /**
     * @Route("/admin")
     */
    public function index(UserRepository $userRepository){
        return $this->render('user_admin/index.html.twig', [
            'users' => $userRepository->findAll()
        ]);
    }

    /**
     * @Route("/admin/user/new")
     */
    public function new(EntityManagerInterface $em, Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $form = $this->createForm(UserAdminType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user = new User();
            $user->setFirstname($data->getFirstname());
            $user->setLastname($data->getLastname());
            $user->setEmail($data->getEmail());
            $user->setDeactivated($data->getDeactivated());
            $user->setPassword($passwordEncoder->encodePassword($user, $data->getPassword()));
            $user->setRoles([]);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "Neuer Benutzer erstellt");

            return $this->redirectToRoute('app_useradmin_index');
        }

        return $this->render('user_admin/new.html.twig', [
            'userForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/user/{id}", name="admin_user_show")
     */
    public function show(User $user){
        return $this->render('user_admin/show.html.twig', [
            'user' => $user,
        ]);

    }

    /**
     * @Route("/admin/user/{id}/makeAdmin", name="admin_user_make_admin")
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
}