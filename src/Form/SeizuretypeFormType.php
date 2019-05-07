<?php

namespace App\Form;

use App\Entity\Seizuretype;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeizuretypeFormType extends AbstractType
{
    /*
     * Mit dem Objekt $builder werden die einzelnen Formularfelder inkl. Clientseitige Validierung definiert.
     * Ohne definierten Type als zweites Attribut wird ein einfaches Textfeld mit dem "TextType" angezeigt
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description')
        ;
    }

    /*
     * Mittels der Funktion configureOptions können optionale Einstellungen definiert werden
     * Zumbeispiel welche Entity verwendet werden soll (Für CRUD-Funktionalitäten)
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Seizuretype::class,
        ]);
    }
}
