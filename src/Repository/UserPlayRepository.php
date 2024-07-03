<?php

namespace App\Repository;

use App\Entity\UserPlay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserPlay|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserPlay|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserPlay[]    findAll()
 * @method UserPlay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserPlayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPlay::class);
    }

    // /**
    //  * @return UserPlay[] Returns an array of UserPlay objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserPlay
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
