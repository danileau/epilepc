<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\Medication;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;


class MedicationFixtures extends BaseFixtures implements DependentFixtureInterface
{
    /**
     * @var DateTime
     */
    private $date;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(10, 'main_medication', function ($count) use ($manager) {
            $entry = new Medication();
            $entry->setName($this->faker->text);
            $entry->setDescription($this->faker->text);
            $entry->setUser($this->getRandomReference('main_users'));
            $entry->setCreatedAt($this->date);
            $entry->setModifiedAt($this->date);
            $date = $this->faker->dateTime;
            $entry->setDateFrom($date);
            $entry->setDateTo($date->add(new \DateInterval('PT10H30S')));
            $entry->setDosage($this->faker->buildingNumber.' mg');
            $entry->setTimestampPrescription($this->faker->dateTime);


            return $entry;


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


