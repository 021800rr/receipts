<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class ReportService
{
    public function __construct(private Connection $db, private string $currency)
    {
    }

    private function filters(?string $from, ?string $to, ?string $household): array
    {
        $w = [];
        $p = [];
        if ($from) {
            $w[] = 'purchase_date >= :from';
            $p['from'] = $from;
        }
        if ($to) {
            $w[] = 'purchase_date <= :to';
            $p['to'] = $to;
        }
        if ($household) {
            $w[] = 'household_id = :hh';
            $p['hh'] = $household;
        }
        return [$w ? ('WHERE ' . implode(' AND ', $w)) : '', $p];
    }

    public function sumByPeriod(?string $from, ?string $to, ?string $hh): int
    {
        [$w, $p] = $this->filters($from, $to, $hh);
        return (int)$this->db->fetchOne("SELECT COALESCE(SUM(amount_grosze),0) FROM report_spend $w", $p);
    }

    public function byCategory(?string $from, ?string $to, ?string $hh): array
    {
        [$w, $p] = $this->filters($from, $to, $hh);
        return $this->db->fetchAllAssociative("SELECT category_id, SUM(amount_grosze) AS sum FROM report_spend $w GROUP BY category_id ORDER BY sum DESC", $p);
    }

    public function byStore(?string $from, ?string $to, ?string $hh): array
    {
        [$w, $p] = $this->filters($from, $to, $hh);
        return $this->db->fetchAllAssociative("SELECT store_id, SUM(amount_grosze) AS sum FROM report_spend $w GROUP BY store_id ORDER BY sum DESC", $p);
    }

    public function byProductTop(?string $from, ?string $to, ?string $hh, int $limit = 10): array
    {
        [$w, $p] = $this->filters($from, $to, $hh);
        $stmt = $this->db->prepare("SELECT product_id, SUM(amount_grosze) AS sum FROM report_spend $w GROUP BY product_id ORDER BY sum DESC LIMIT :lim");
        foreach ($p as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        return $stmt->executeQuery()->fetchAllAssociative();
    }
}
