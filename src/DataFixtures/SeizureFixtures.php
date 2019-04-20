<?php

namespace App\DataFixtures;

use App\Entity\Seizure;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;


class SeizureFixtures extends BaseFixtures implements DependentFixtureInterface
{


    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(10, 'main_seizure', function($count) use ($manager) {
            $seizure = new Seizure();
            $seizure->setDescription($this->faker->text);
            $seizure->setTimestampWhen($this->faker->dateTime);
            $seizure->setCreatedAt($this->faker->dateTime);
            $seizure->setModifiedAt($this->faker->dateTime);
            $seizure->setUser($this->getRandomReference('main_users'));


            return $seizure;


        });
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class
        ];
    }
}
