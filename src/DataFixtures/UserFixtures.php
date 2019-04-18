<?php

namespace App\DataFixtures;

use App\Entity\User;

use Doctrine\Common\Persistence\ObjectManager;

class UserFixtures extends BaseFixtures
{
    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(User::class, 10, function (User $user, $count){

            $user->setFirstname('Danilo')
                ->setLastname('Licitra')
                ->setEmail('danilo.licitra' . $count . '@gmail.com')
                ->setPassword('12345')
                ->setDeactivated(false);

        });
        $manager->flush();
    }
}
