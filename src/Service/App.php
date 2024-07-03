<?php

namespace App\Service;
#use Doctrine\Common\Persistence\ManagerRegistry;

use App\Entity\Gift;
use App\Entity\Lottery;
use App\Entity\UserPlay;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

class App
{
    protected $em;
    private $now;
    public $startAt;
    public $endAt;
    private $lottery;
    private $session;

    public function __construct(EntityManagerInterface $em, RequestStack $requestStack)
    {
        $this->em = $em;
        $this->session = $requestStack->getSession();

        $this->now = new \DateTime();
        $this->startAt = new \DateTime($_ENV['START_AT']);
        $this->endAt = new \DateTime($_ENV['END_AT']);

        $this->lottery = $this->em->getRepository(Lottery::class)->findMostRecentLottery();
        $this->startAt = $this->lottery ? $this->lottery->getStartAt() : null;
        $this->endAt = $this->lottery ? $this->lottery->getEndAt() : null;
    }

    public function getLottery() {
        return $this->lottery;
    }

    public function getName() {
        return 'App';
    }

    public function play(UserPlay $user) {
        $em = $this->em;
        $lottery = $this->lottery;
        $item = $user;
        $product = null;
        
        $now = new \DateTime('now');
        $sql = "
          SELECT g.*, p.picture, p.name
          FROM gift as g
          LEFT JOIN product as p on g.product_id = p.id
          WHERE g.user_id IS NULL
          AND g.instant_at <= '" . $now->format('Y-m-d H:i:s') . "'
          AND g.lottery_id = " . $lottery->getId() . "
          ORDER BY g.instant_at
          ";
          
        $qb = $em->getConnection()->executeQuery($sql);
        $results = $qb->fetchAllAssociative();

        $gift = null;
  
        if (count($results) > 0) {
          $gift = $em->getRepository(Gift::class)->find($results[0]['id']);
          if(!$gift || $gift->getWinAt() !== null) {
            return null;
          }	
          $item->setGift($gift);
          $gift->setUser($item);
          $gift->setWinAt(new \DateTime('now'));
          $em->persist($gift);
          $product = $gift->getProduct();
        }
  
        $em->flush();

        return $product;
    }

    public function opening()
    {
        if(!$this->lottery) return 'in_coming';
        if ($this->now >= $this->startAt && $this->now <= $this->endAt) {
            return true;
        } else if ($this->now >= $this->endAt) {
            return 'in_coming';
        } else {
            return 'in_coming';
        }
    }

    public function hasPlayed() {
        $userPlayId = $this->session->get("userPlayId");
        
        $userPlay = null;
        if($userPlayId) $userPlay = $this->em->getRepository(UserPlay::class)->find($userPlayId);

        return $userPlay !== null;
    }

}
