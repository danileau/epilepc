<?php


namespace App\Form;


use App\Entity\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileFormType extends AbstractType
{

    /*
     * Mit dem Objekt $builder werden die einzelnen Formularfelder inkl. Clientseitige Validierung definiert.
     * Ohne definierten Type als zweites Attribut wird ein einfaches Textfeld mit dem "TextType" angezeigt
     * "EmailType" erstellt ein Emailfeld an, welches eine valide Emailadresse benötigt
     * "DateTimeType" erstellt ein Datumauswahlfeld mit Datum und Zeitangabe
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class)
            ->add('firstname', TextType::class, ['attr' => ['maxlength' => 25], 'help' => 'form.account.max.length'])
            ->add('lastname', TextType::class, ['attr' => ['maxlength' => 25],'help' => 'form.account.max.length'])
            ->add('diagnose', TextType::class, [
                'required' => false,
                'attr' => ['maxlength' => 250],
                'help' => 'form.account.diagnose.max.length'])
            ->add('agreed_terms_at', DateTimeType::class, [
                'widget' => 'single_text',
                'disabled' => true
            ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }


}
