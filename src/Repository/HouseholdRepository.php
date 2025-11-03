<?php

namespace App\Repository;

use App\Entity\Household;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Household>
 * @method Household|null find($id, $lockMode = null, $lockVersion = null)
 * @method Household|null findOneBy(array $criteria, array $orderBy = null)
 * @method Household[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HouseholdRepository extends ServiceEntityRepository
{
    use FindTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Household::class);
    }
}
