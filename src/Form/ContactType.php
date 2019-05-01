<?php

namespace App\Form;

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('from', EmailType::class)
            ->add('message', TextareaType::class)
            ->add('thema', ChoiceType::class, [
                'choices'  => [
                    'Frage / Support' => 'Frage/Support',
                    'Sorgen' => 'Sorgen',
                    'Administratives' => 'Administratives',
                    'Interesse an weiteren Produkten' => 'Interesse an Produkten',
                    'Interesse an Mitarbeit'  => 'Interesse an Mitarbeit',
                    'Idee' => 'Idee'
                ]
            ])
            ->add('copy', CheckboxType::class, [
                'label'    => 'Schick mit eine Kopie dieser Nachticht',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
