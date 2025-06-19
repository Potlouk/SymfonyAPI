<?php

namespace App\DataFixtures;

use App\Entity\Setting;
use App\Exception\LogicException;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SettingsFixture extends Fixture
{

    public function load(ObjectManager $manager ): void
    {
        $settingsData = file_get_contents(__DIR__ . '/Data/Settings.json');
        if (false === $settingsData)
            throw new LogicException('Settings.json file is empty');

        $settings = (new Setting)->setId(1)
            ->setData(json_decode($settingsData, true, 512, JSON_THROW_ON_ERROR));

        $manager->persist($settings);
        $manager->flush();
    }
}
