<?php

namespace App\Repository;

use App\Entity\Lottery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Lottery|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lottery|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lottery[]    findAll()
 * @method Lottery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LotteryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lottery::class);
    }

    public function findMostRecentLottery()
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('l')
            ->andWhere('l.start_at <= :now')
            ->andWhere('l.end_at >= :now')
            ->setParameter('now', $now)
            ->orderBy('l.start_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // /**
    //  * @return Lottery[] Returns an array of Lottery objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Lottery
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
