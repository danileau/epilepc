<?php


namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;

class PasswordChangeType extends AbstractType
{
    /*
     * Mit dem Objekt $builder werden die einzelnen Formularfelder inkl. Clientseitige Validierung definiert.
     * Ohne definierten Type als zweites Attribut wird ein einfaches Textfeld angezeigt
     * "PasswordType" erstellt ein Passwortfeld welches die Eingabe maskiert
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('old_password', PasswordType::class, array(
                'label' => 'Altes Passwort',
            ))
            ->add('new_password', PasswordType::class, array(
                'label' => 'Neues Passwort',
            ))
            ->add('new_password_confirm', PasswordType::class, array(
                'label' => 'Neues Passwort wiederholen',
            ));
    }

}
