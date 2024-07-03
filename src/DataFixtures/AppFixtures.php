<?php

namespace App\DataFixtures;

use App\Entity\CotisationPaymentMethod;
use App\Entity\CotisationUnionParameter;
use App\Entity\CotisationUnionRule;
use Doctrine\Bundle\FixturesBundle\Fixture;

use App\Entity\DataRole;
use App\Entity\DataDepartment;
use App\Entity\DataImport;
use App\Entity\DataModule;
use App\Entity\DataGroupOperator;
use App\Entity\DataOperator;

class AppFixtures extends Fixture
{
    public function load(\Doctrine\Persistence\ObjectManager $manager)
    {
      $now = new \DateTime();

      //======================================== ROLES
      $data = new DataRole();
      $data->setName('Limeo');
      $data->setSlug('ROLE_LIMEO');
      $data->setIsException(1);
      $manager->persist($data);
      $manager->flush();

      $data = new DataRole();
      $data->setName('Administrateur');
      $data->setSlug('ROLE_ADMIN');
      $data->setIsException(0);
      $manager->persist($data);
      $manager->flush();
    }
}
