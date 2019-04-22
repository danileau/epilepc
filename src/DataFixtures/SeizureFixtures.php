<?php

namespace App\DataFixtures;

use App\Entity\Seizure;
use App\Entity\Seizuretype;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;


class SeizureFixtures extends BaseFixtures implements DependentFixtureInterface
{


    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(10, 'main_seizure', function($count) use ($manager) {
            $seizure = new Seizure();
            $seizure->setTitle($this->faker->text);
            $seizure->setDescription($this->faker->text);
            $seizure->setTimestampWhen($this->faker->dateTime);
            $seizure->setCreatedAt($this->faker->dateTime);
            $seizure->setModifiedAt($this->faker->dateTime);
            $seizure->setUser($this->getRandomReference('main_users'));
            $seizure->setSeizuretype($this->getRandomReference('main_seizuretype'));


            return $seizure;


        });
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            SeizuretypeFixtures::class,
            UserFixtures::class
        ];
    }
}
