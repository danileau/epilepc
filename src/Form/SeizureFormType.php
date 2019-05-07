<?php

namespace App\Form;

use App\Entity\Seizure;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class SeizureFormType extends AbstractType
{

    /*
     * Mit dem Objekt $builder werden die einzelnen Formularfelder inkl. Clientseitige Validierung definiert.
     * Ohne definierten Type als zweites Attribut wird ein einfaches Textfeld mit dem "TextType" angezeigt
     * "EntityType" erstellt ein Auswahlfeld welches mit den Inhalten des definierten Entities
     * und den Inhalten in der Datenbanktabelle gefüllt ist
     * "DateTimeType" erstellt ein Datumauswahlfeld mit Datum und Zeitangabe
     */
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

    /*
     * Mittels der Funktion configureOptions können optionale Einstellungen definiert werden
     * Zumbeispiel welche Entity verwendet werden soll (Für CRUD-Funktionalitäten)
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Seizure::class,
        ]);
    }
}
