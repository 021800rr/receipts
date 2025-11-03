<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Household;
use App\Entity\Product;
use App\Entity\Store;

trait FindTrait
{
    public function findByTerm(string $term): mixed {
        return $this->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE :t')
            ->setParameter('t', '%'.mb_strtolower((string) $term).'%')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    /** @return Category[]|Household[]|Product[]|Store[] */
    public function findAll(): array
    {
        return $this->findBy([], ['name' => 'ASC']);
    }
}
