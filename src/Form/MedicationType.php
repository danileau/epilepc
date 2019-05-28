<?php

namespace App\Form;

use App\Entity\Medication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MedicationType extends AbstractType
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
            ->add('dosage')
            ->add('date_from', DateTimeType::class, [
                'widget' => 'single_text'
            ])
            ->add('date_to', DateTimeType::class, [
                'widget' => 'single_text'
            ])
            ->add('timestamp_prescription', DateTimeType::class, [
                'widget' => 'single_text'
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
            'data_class' => Medication::class,
        ]);
    }
}
