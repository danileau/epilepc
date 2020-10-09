<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Sodium\add;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    /*
     * Die buildForm() Funktion baut das Formular welches angezeigt werden soll auf
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /*
         * Mit dem Objekt $builder werden die einzelnen Formularfelder inkl. Clientseitige Validierung definiert.
         * Ohne definierten Type als zweites Attribut wird ein einfaches Textfeld angezeigt
         * "EmailType" erstellt ein Emailfeld an, welches eine valide Emailadresse benötigt
         * "TextareaType" erstellt ein Freitextfeld
         * "ChoiceType" erstellt ein Dropdown mit verschiedenen Auswahlmöglichkeiten
         * "CheckboxType" erstellt eine Auswahlbox
         *
         */
        $builder
            ->add('name')
            ->add('from', EmailType::class)
            ->add('message', TextareaType::class)
            ->add('thema', ChoiceType::class, [
                'choice_translation_domain' => true,
                'choices'  => [
                    'Frage / Support' => 'Frage/Support',
                    'Sorgen' => 'Sorgen',
                    'Administratives' => 'Administratives',
                    'Interesse an weiteren Produkten' => 'Interesse an Produkten',
                    'Interesse an Mitarbeit'  => 'Interesse an Mitarbeit',
                    'Idee' => 'Idee'
                ]
            ])
            ->add('a_password', TextType::class, [
                'attr' => ['style' => 'display:none !important', 'tabindex' => '-1', 'autocomplete' => 'nope'],
                'label' => false,
                'required' => false
            ])
            ->add('recaptcha_token', HiddenType::class, [
                'mapped' => false
            ])

            ->add('copy', CheckboxType::class, [
                'label'    => 'Schick mir eine Kopie dieser Nachticht',
                'required' => false,
            ]);
    }

    /*
     * Mittels der Funktion configureOptions können optionale Einstellungen definiert werden
     * Zumbeispiel welche Entity verwendet werden soll (Für CRUD-Funktionalitäten)
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
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
