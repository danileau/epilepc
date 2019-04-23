<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserRegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', null, [
                'label' => 'Vorname'
            ])
            ->add('lastname', null, [
                'label' => 'Nachname'
            ])
            ->add('email')

            // Absichtlich ein Feld, welches nicht in der Datenbank existiert,
            // um kein nicht verschlüsseltes Passwort aus Versehen in die Datenbank zu speichern
            // Dank "mapped"
            // Einschränkungen mittels 'constraints'
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Passwort'
                ],
                'second_options' => [
                    'label' => 'Passwort wiederholen'
                ],
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
                'label' => 'Ich akzeptiere die Nutzungsbedingungen',
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Um alles sauber und richtig zu machen, musst du unseren Nutzungsbedingungen zustimmen. Sorry..'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
