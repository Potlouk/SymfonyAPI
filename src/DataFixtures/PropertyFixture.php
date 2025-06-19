<?php

namespace App\DataFixtures;

use App\Entity\Property;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PropertyFixture extends Fixture
{

    public function load(ObjectManager $manager ): void
    {

        $properties = [];

        foreach ($properties as $property) {
            $temp =  explode(',',$property);
            $property = (new Property)->setData([
                    'owner'                 => $temp[0]?? '',
                    'manager'               => $temp[1]?? '',
                    'landlord'              => $temp[2]?? '',
                    'name'                  => $temp[3]?? '',
                    'street'                => $temp[4]?? '',
                    'city'                  => $temp[5]?? '',
                    'state'                 => $temp[6]?? '',
                    'postal'                => $temp[7]?? '',
                    'builtDate'             => $temp[8]?? '',
                    'acquiredDate'          => $temp[9]?? '',
                    'amortizeItems'         => $temp[10]?? '',
                    'amortizeAppliances'    => $temp[11]?? '',
                    'assessmentFrequency'   => $temp[12]?? '',  
            ])->setArchived(false);
            $manager->persist($property);
        }
        $manager->flush();
    }
}
