<?php

namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;


class EventFixtures extends BaseFixtures implements DependentFixtureInterface
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
        $this->createMany(100, 'main_event', function ($count) use ($manager) {
            $entry = new Event();
            $entry->setName($this->faker->text);
            $entry->setDescription($this->faker->text);
            $entry->setUser($this->getRandomReference('main_users'));
            $entry->setCreatedAt($this->date);
            $entry->setModifiedAt($this->date);
            $entry->setTimestampWhen($this->faker->dateTime);


            return $entry;


        });
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            SeizureFixtures::class
        ];
    }
}


