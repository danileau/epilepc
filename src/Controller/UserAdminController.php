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
    public function index(){

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

            return $this->redirectToRoute('admin_user_list');
        }

        return $this->render('user_admin/new.html.twig', [
            'userForm' => $form->createView(),
        ]);
    }
    /**
     * @Route("/admin/user/list", name="admin_user_list")
     */
    public function list(UserRepository $userRepository){
        $users = $userRepository->findAll();

        return $this->render('user_admin/list.html.twig', [
            'users' => $users
        ]);
    }
}