<?php

namespace App\DataFixtures;

use App\Entity\Diaryentry;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;


class DiaryentryFixtures extends BaseFixtures implements DependentFixtureInterface
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
        $this->createMany(100, 'main_diaryentry', function($count) use ($manager) {
            $entry = new Diaryentry();
            $entry->setTitle($this->faker->text);
            $entry->setContent($this->faker->text);
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
            UserFixtures::class
        ];
    }
}
