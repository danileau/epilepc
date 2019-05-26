<?php


namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

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
                'constraints' => [
                    new NotBlank([
                        'message' => 'Wähle ein sicheres Passwort aus'
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*\d.*\d)(?=.*?[^\w\s]).{8,}$/',
                        'message' => 'Dein Passwort muss die folgenden Anforderungen erfüllten: Gross-, Kleinschreibung, min. 2 Zahlen, min. 1 Sonderzeichen und Mindestlänge 8 Zeichen'
                    ])
                ]
            ))
            ->add('new_password_confirm', PasswordType::class, array(
                'label' => 'Neues Passwort wiederholen',
            ));
    }

}
