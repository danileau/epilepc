<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    // Kontaktformular erstellen und Mailing Logik
    /**
     * @Route("/contact", name="contact")
     */
    public function index(Request $request, \Swift_Mailer $mailer)
    {
        // Erstellt das Kontaktformular
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $contactFormData = $form->getData();

            // Handles the Honeypot.
            // If the textfield "a_password" is not null -> Spam
            // https://stackoverflow.com/questions/36227376/better-honeypot-implementation-form-anti-spam
            $honeypot = FALSE;
            if ($contactFormData['a_password'] != null) {
                $honeypot = TRUE;
                # treat as spambot & don't send a Mail and redirect directly
                return $this->redirectToRoute('contact');
            } else {
                # process as normal
                // Generiert und versendet das Email
                $message = (new \Swift_Message('Neue Kontaktformular-Nachricht auf epilepc.ch erhalten!'))
                    ->setFrom('no-reply@epilepc.ch');
                $message->setTo('info@epilepc.ch');

                if ($contactFormData['copy'] == true) {
                    $message->setCc($contactFormData['from']);
                }

                $message->setBody(
                    $this->renderView(
                    // templates/emails/registration.html.twig
                        'mail/contact.mail.html.twig',
                        [
                            'name' => $contactFormData['name'],
                            'from' => $contactFormData['from'],
                            'message' => $contactFormData['message'],
                            'thema' => $contactFormData['thema'],
                            'copy' => $contactFormData['copy']
                        ]
                    ),
                    'text/html'
                );

                $mailer->send($message);
                $this->addFlash('success', 'Ihre Nachricht wurde erfolgreich versendet!');

                return $this->redirectToRoute('contact');
            }
        }
        return $this->render('landing/contact/index.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }
}
