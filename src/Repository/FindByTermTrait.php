<?php

namespace App\Repository;

trait FindByTermTrait
{
    public function findByTerm(string $term): mixed {
        return $this->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE :t')
            ->setParameter('t', '%'.mb_strtolower((string) $term).'%')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }
}
