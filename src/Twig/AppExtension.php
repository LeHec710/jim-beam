<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

#use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Persistence\ManagerRegistry;
use App\Service\App;

use App\Entity\User;
use App\Entity\DataState;
use App\Entity\Folder;
use App\Entity\CalendarEvent;

class AppExtension extends AbstractExtension
{
    protected $em;

    public function __construct(ManagerRegistry $em, private readonly App $app)
    {
        $this->em = $em;
    }
    public function getName() {
        return 'AppExtension';
    }

    public function getFunctions()
    {
      return [
        new TwigFunction('getUser', $this->getUser(...)),
      ];
    }

    public function getUser($id)
    {
      $user = $this->em->getRepository(User::class)->find($id);
      return $user;
    }
}
