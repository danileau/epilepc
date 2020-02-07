<?php

namespace App\Form;

use App\Entity\Event;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{

    /*
     * Mit dem Objekt $builder werden die einzelnen Formularfelder inkl. Clientseitige Validierung definiert.
     * Ohne definierten Type als zweites Attribut wird ein einfaches Textfeld angezeigt
     * "DateTimeType" erstellt ein Datumauswahlfeld mit Datum und Zeitangabe
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [])
            ->add('description')
            ->add('timestamp_when', DateTimeType::class, [
                'widget' => 'single_text',
                'choice_translation_domain' => true,
                'help' => 'Zeitpunkt des Ereignisses im Format TT.MM.YYYY HH:MM'
            ])


        ;
    }

    /*
     * Mittels der Funktion configureOptions können optionale Einstellungen definiert werden
     * Zumbeispiel welche Entity verwendet werden soll (Für CRUD-Funktionalitäten)
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
