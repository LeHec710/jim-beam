<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use App\Entity\User;

class UserFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager)
    {
      $user = new User();
      $user->setFirstname('Limeo');
      $user->setLastname('Interactive');
      $user->setEmail('support@limeo.com');
      $user->setIsActive(1);
      $user->setToken(uniqid());
      $user->setRoles(["ROLE_LIMEO", "ROLE_ADMIN", "ROLE_ALLOWED_TO_SWITCH", "ROLE_USER_BACKEND"]);

      $password = $this->hasher->hashPassword($user, 'limeo');
      $user->setPassword($password);

      $manager->persist($user);
      $manager->flush();

      $user = new User();
      $user->setFirstname('admin');
      $user->setLastname('admin');
      $user->setEmail('admin@limeo.com');
      $user->setIsActive(1);
      $user->setToken(uniqid());
      $user->setRoles(["ROLE_ADMIN"]);

      $password = $this->hasher->hashPassword($user, 'limeo');
      $user->setPassword($password);

      $manager->persist($user);
      $manager->flush();
    }
}
