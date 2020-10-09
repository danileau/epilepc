<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserRegistrationFormType extends AbstractType
{

    /*
     * Mit dem Objekt $builder werden die einzelnen Formularfelder inkl. Clientseitige Validierung definiert.
     * Ohne definierten Type als zweites Attribut wird ein einfaches Textfeld mit dem "TextType" angezeigt
     * "EmailType" erstellt ein Emailfeld an, welches eine valide Emailadresse benötigt
     * "PasswordType" erstellt ein Passwortfeld welches die Eingabe maskiert
     * "CheckboxType" erstellt eine Auswahlbox
     * Zusätzlich kann für jedes Feld das Label überschrieben werden und eine message definiert werden
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', null, [
                'label' => 'Vorname'
            ])
            ->add('lastname', null, [
                'label' => 'Nachname'
            ])
            ->add('email', EmailType::class)

            // Absichtlich ein Feld, welches nicht in der Datenbank existiert,
            // um kein nicht verschlüsseltes Passwort aus Versehen in die Datenbank zu speichern
            // Dank "mapped" => false
            // Einschränkungen mittels 'constraints'
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'pw.notBlank'
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*\d.*\d)(?=.*?[^\w\s]).{8,}$/',
                        //'message' => 'Dein Passwort muss die folgenden Anforderungen erfüllten: Gross-, Kleinschreibung, min. 2 Zahlen, min. 1 Sonderzeichen und Mindestlänge 8 Zeichen'
                        'message' => 'pw.regex'
                    ])
                ]
            ])
            ->add('recaptcha_token', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'agreeTerms'
                    ])
                ]
            ])
        ;
    }

    /*
     * Mittels der Funktion configureOptions können optionale Einstellungen definiert werden
     * Zum Beispiel welche Entity verwendet werden soll (Für CRUD-Funktionalitäten)
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            // enable/disable CSRF protection for this form
            'csrf_protection' => true,
            // the name of the hidden HTML field that stores the token
            'csrf_field_name' => '_token',
            // an arbitrary string used to generate the value of the token
            // using a different string for each form improves its security
            'csrf_token_id'   => 'contact_item',
        ]);
    }
}
