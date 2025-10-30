<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class ReportService
{
    public function __construct(private readonly Connection $db)
    {
    }

    private function filters(?string $from, ?string $to, ?string $household, ?string $store = null, ?string $category = null, ?string $product = null): array
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
        if ($store) {
            $w[] = 'store_id = :store';
            $p['store'] = $store;
        }
        if ($category) {
            $w[] = 'category_id = :category';
            $p['category'] = $category;
        }
        if ($product) {
            $w[] = 'product_id = :product';
            $p['product'] = $product;
        }
        return [$w ? ('WHERE ' . implode(' AND ', $w)) : '', $p];
    }

    public function sumByPeriod(?string $from, ?string $to, ?string $hh, ?string $store = null, ?string $category = null, ?string $product = null): float
    {
        [$w, $p] = $this->filters($from, $to, $hh, $store, $category, $product);
        $res = $this->db->fetchOne("SELECT COALESCE(SUM(amount),0) FROM report_spend $w", $p);
        return (float)$res;
    }

    public function byCategory(?string $from, ?string $to, ?string $hh, ?string $store = null, ?string $category = null, ?string $product = null): array
    {
        [$w, $p] = $this->filters($from, $to, $hh, $store, $category, $product);
        return $this->db->fetchAllAssociative("SELECT category_id, SUM(amount) AS sum FROM report_spend $w GROUP BY category_id ORDER BY sum DESC", $p);
    }

    public function byStore(?string $from, ?string $to, ?string $hh, ?string $store = null, ?string $category = null, ?string $product = null): array
    {
        [$w, $p] = $this->filters($from, $to, $hh, $store, $category, $product);
        return $this->db->fetchAllAssociative("SELECT store_id, SUM(amount) AS sum FROM report_spend $w GROUP BY store_id ORDER BY sum DESC", $p);
    }

    public function byProductTop(?string $from, ?string $to, ?string $hh, int $limit = 10, ?string $store = null, ?string $category = null, ?string $product = null): array
    {
        [$w, $p] = $this->filters($from, $to, $hh, $store, $category, $product);
        $stmt = $this->db->prepare("SELECT product_id, SUM(amount) AS sum FROM report_spend $w GROUP BY product_id ORDER BY sum DESC LIMIT :lim");
        foreach ($p as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function compareHouseholds(?string $from, ?string $to, ?string $store = null, ?string $category = null, ?string $product = null): array
    {
        [$w, $p] = $this->filters($from, $to, null, $store, $category, $product);
        return $this->db->fetchAllAssociative("SELECT household_id, SUM(amount) AS sum FROM report_spend $w GROUP BY household_id ORDER BY sum DESC", $p);
    }
}
