<?php

namespace App\Security\Voter;

use App\Entity\Seizure;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class SeizureVoter extends Voter
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports($attribute, $subject)
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, ['MANAGE'])
            && $subject instanceof Seizure;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var Seizure $subject */
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'MANAGE':

                // If the user_id from the seizure does match the id from the logged in User, return true
                if ($subject->getUser()->getId() == $user->getId()){
                    return true;
                }

                // If the logged in user, is Admin, return true
                //if ($this->security->isGranted('ROLE_ADMIN')){
                //    return true;
                //}

                // If nothing matches, deny access
                return false;


        }

        return false;
    }
}
