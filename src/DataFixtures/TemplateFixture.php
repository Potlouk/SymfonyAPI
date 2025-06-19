<?php

namespace App\DataFixtures;

use App\Entity\Template;
use App\Exception\LogicException;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TemplateFixture extends Fixture
{

    public function load(ObjectManager $manager ): void
    {
        $templateData = file_get_contents(__DIR__ . '/Data/Template.json');
        if (false === $templateData)
            throw new LogicException('Template.json file is empty');

        $template = (new Template())->setData(
            json_decode($templateData, true, 512, JSON_THROW_ON_ERROR)
        )->setId(1)
            ->setName('Default')
            ->setInfo(json_decode('{
                "config": {
                    "imageTags": [],
                    "description": false,
                    "categoryTags": []
                }
            }', true, 512, JSON_THROW_ON_ERROR));

        $manager->persist($template);
        $manager->flush();
    }
}
