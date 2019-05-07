<?php

namespace App\Security\Voter;

use App\Entity\Event;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class EventVoter
 * @package App\Security\Voter
 * Alle Voter werden jedes Mal aufgerufen, wenn  die isGranted()-Methode auf dem
 * authorization-checker von Symfony verwendet oder denyAccessUnlessGranted() in einem
 * Controller aufgerufen wird.
 * Hier kann eigene Logik in die Authentisierung implementiert werden
 */
class EventVoter extends Voter
{

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool- prüft ob 'MANAGE' in der Annotation der Route definiert ist.
     */
    protected function supports($attribute, $subject)
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, ['MANAGE'])
            && $subject instanceof Event;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool - Falls 'MANAGE' gesetzt ist, kann die Seite nur aufgerufen werden, wenn die user_id vom aktuellen
     * Eintrag mit der eingeloggten user_id übereinstimmt
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var Event $subject */
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'MANAGE':

                // If the user_id from the event does match the id from the logged in User, return true
                if ($subject->getUser()->getId() == $user->getId()) {
                    return true;
                }

                // If the logged in user, is Admin, return true
                //if ($this->security->isGranted('ROLE_ADMIN')) {
                //    return true;
                //}

                // If nothing matches, deny access
                return false;
        }
        return false;
    }
}
