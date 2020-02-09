<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordChangeType;
use App\Form\PasswordForgotType;
use App\Form\UserRegistrationFormType;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{

    // Loginfunktion
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, LoggerInterface $logger): Response
    {
        // Falls bereits eingeloggt, reditect zu /app
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_dashboard');
        }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // Bei fehlerhaftem Login -> Versuch protokollieren

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        if ($error){
            $logger->error('------------------- Fehlerhafter Loginversuch von '.$lastUsername.' -------------------------------');
        }
        return $this->render('app/authentication/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    // Registrierungsfunktion mit Mailversand
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, GuardAuthenticatorHandler $guardHandler, LoginFormAuthenticator $formAuthenticator, \Swift_Mailer $mailer, TranslatorInterface $translator)
    {

        $form = $this->createForm(UserRegistrationFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){

            /** @var User $user */
            $user = $form->getData();
            // Verschlüsselt das eingegebene Passwort und SET ins User Objekt
            $user->setPassword($passwordEncoder->encodePassword(
                $user,
                $form['plainPassword']->getData()
            ));

            $user->setFirstname($user->getFirstname());
            $user->setLastname($user->getLastname());
            $user->setEmail($user->getEmail());
            $user->setDeactivated(0);

            if (true === $form['agreeTerms']->getData()){
                $user->agreeTerms();
            }
            $em = $this->getDoctrine()->getManager();
            // Daten in Datenbank speichern
            $em->persist($user);
            $em->flush();

            // Build & Versand Registrierungsbestätigung
            $message = (new \Swift_Message($translator->trans('Ihre Registrierung bei epilepc')))
                ->setFrom('no-reply@epilepc.ch')
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        'mail/register.mail.html.twig',
                        [
                            'name' => $user->getFirstname(),
                        ]
                    ),
                    'text/html'
                );

            $mailer->send($message);
            $this->addFlash('success', $translator->trans('Herzliche Gratulation! Ihre Registrierung ist abgeschlossen! Falls Sie unsere Bestätigungsmail nicht erhalten haben, prüfen Sie bitte Ihrem Spam-Ordner.'));

            // Nach Erstellung des Users, Login
            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $formAuthenticator,
                'main'
            );
        }


        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_dashboard');
        }
        return $this->render('app/authentication/register.html.twig', [
            'registrationForm' => $form->createView()
        ]);
    }


    /**
     * @Route("/forgot", name="app_forgot-password")
     */
    public function forgot(Request $request, UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer, TranslatorInterface $translator){
        $form = $this->createForm(PasswordForgotType::class);
        $em = $this->getDoctrine()->getManager();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data["email"];
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $email]);

            $data = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz()$-_';
            $randomPW = substr(str_shuffle($data), 0, 12);
            if ($user != null) {
                $newEncodedPassword = $passwordEncoder->encodePassword($user, $randomPW);
                $user->setPassword($newEncodedPassword);

                $em->persist($user);
                $em->flush();

                $message = (new \Swift_Message($translator->trans('epilepc - Ihr Passwort wurde zurückgesetzt')))
                    ->setFrom('no-reply@epilepc.ch')
                    ->setTo($email)
                    ->setBody(
                        $this->renderView(
                            'mail/forgot.pw.mail.html.twig',
                            [
                                'password' => $randomPW,

                            ]
                        ),
                        'text/html'
                    );
                $mailer->send($message);
                $this->addFlash('success', $translator->trans('Passwort erfolgreich zurückgesetzt & versendet!'));

                return $this->redirectToRoute('app_landingpage');
            } else {
                $form->addError(new FormError($translator->trans('Die angegebene Email-Adresse ist nicht registriert')));
            }
        }

        return $this->render('app/authentication/forgot.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
    }

}
