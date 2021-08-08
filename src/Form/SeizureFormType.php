<?php

namespace App\Form;

use App\Entity\Seizure;
use Doctrine\DBAL\Types\BooleanType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('title', TextType::class, [])
            ->add('description')
            ->add('timestamp_when', DateTimeType::class, [
                'widget' => 'single_text',
                'choice_translation_domain' => true,
                'help' => 'Zeitpunkt des Anfalls im Format TT.MM.YYYY HH:MM',
            ])
            ->add('seizuretype', EntityType::class, [
                'choice_translation_domain' => true,
                'placeholder' => 'Anfallstyp auswählen',
                'class' => \App\Entity\Seizuretype::class,
                'help' => 'Wenn unbekannt, fragen Sie Ihren Arzt nach der auszuwählenden Anfallsart'
            ]);

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
