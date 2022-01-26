<?php

namespace App\DataFixtures;

use App\Entity\Seizure;
use App\Entity\Seizuretype;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;


class SeizuretypeFixtures extends BaseFixtures
{


    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(30, 'main_seizuretype', function($count) use ($manager) {
            $seizuretype = new Seizuretype();
            $seizuretype->setName($this->faker->text);
            $seizuretype->setDescription($this->faker->text);
            return $seizuretype;


        });
        $manager->flush();
    }

}
