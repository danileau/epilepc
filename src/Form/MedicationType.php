<?php

namespace App\Form;

use App\Entity\Medication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
            ->add('dosage', null, [
                'help' => 'Dosierung in mg. z. B. 3x15mg pro Tag'
            ])
            ->add('date_from', DateTimeType::class, [
                'widget' => 'single_text',
                'choice_translation_domain' => true,
                'help' => 'Start Medikamenteneinnahme im Format TT.MM.YYYY HH:MM'
            ])
            ->add('date_to', DateTimeType::class, [

                'widget' => 'single_text',
                'choice_translation_domain' => true,
                'help' => 'Ende Medikamenteneinnahme im Format TT.MM.YYYY HH:MM'
            ])
            ->add('timestamp_prescription', DateType::class, [

                'widget' => 'single_text',
                'choice_translation_domain' => true,
                'help' => 'Ausstelldatum des Rezepts'
            ])
            ->add('emergency_med', CheckboxType::class, [
                'label'    => 'Notfallmedikament eingenommen?',
                'required' => false
            ]);
    }

    /*
     * Mittels der Funktion configureOptions können optionale Einstellungen definiert werden
     * Zum Beispiel welche Entity verwendet werden soll (Für CRUD-Funktionalitäten)
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Medication::class,
        ]);
    }
}
