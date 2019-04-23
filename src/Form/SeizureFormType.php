<?php

namespace App\Form;

use App\Entity\Seizure;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class SeizureFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('title')
            ->add('description')
            ->add('timestamp_when', DateTimeType::class, [
                'widget' => 'single_text'
            ])
            ->add('seizuretype', EntityType::class, [
                'placeholder' => 'Anfallstyp auswählen',
                'class' => \App\Entity\Seizuretype::class
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Seizure::class,
        ]);
    }
}
