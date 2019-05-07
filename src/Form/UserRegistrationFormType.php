<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

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
                        'message' => 'Wähle ein sicheres Passwort aus'
                    ]),
                    new Length([
                        'min' => 5,
                        'minMessage' => 'Also mehr als 5 Zeichen müssen es schon sein...'
                    ])
                ]
            ])

            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Um unser Service nutzen zu können, musst du unseren Nutzungsbedingungen zustimmen'
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
        ]);
    }
}
