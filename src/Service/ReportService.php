<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class ReportService
{
    public function __construct(private readonly Connection $db){}

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

    // src/Service/ReportService.php
    public function allPurchases(
        ?string $from,
        ?string $to,
        ?string $hh,
        ?string $store = null,
        ?string $category = null,
        ?string $product = null,
        string $sort = 'purchase_date',
        string $dir  = 'desc',
        int $limit   = 50,
        int $offset  = 0
    ): array {
        [$w, $p] = $this->filters($from, $to, $hh, $store, $category, $product);

        // Whitelist kolumn do sortowania
        $sortable = [
            'purchase_date' => 'rs.purchase_date',
            'household'     => 'h.name',
            'store'         => 's.name',
            'category'      => 'c.name',
            'product'       => 'p.name',
            'quantity'      => 'rs.quantity',
            'amount'        => 'rs.amount',
        ];
        $sortExpr = $sortable[$sort] ?? $sortable['purchase_date'];
        $dir      = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $sql = "
            SELECT
                rs.purchase_date,
                rs.household_id, h.name  AS household_name,
                rs.store_id,     s.name  AS store_name,
                rs.category_id,  c.name  AS category_name,
                rs.product_id,   p.name  AS product_name,
                rs.quantity,
                rs.amount
            FROM report_spend rs
            JOIN household h ON h.id = rs.household_id
            JOIN store     s ON s.id = rs.store_id
            JOIN category  c ON c.id = rs.category_id
            JOIN product   p ON p.id = rs.product_id
            $w
            ORDER BY $sortExpr $dir
            LIMIT :lim OFFSET :off
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($p as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, \PDO::PARAM_INT);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function sumByProductAcrossStores(
        ?string $from,
        ?string $to,
        ?string $hh,
        ?string $category = null,   // możesz zostawić filtr kategorii, ale nie grupujemy po niej
        ?string $product = null,    // opcjonalnie zawęzić do jednego produktu
        string $sort = 'sum',       // 'sum' | 'quantity' | 'name'
        string $dir  = 'desc',      // 'asc' | 'desc'
        int $limit   = 100,
        int $offset  = 0
    ): array {
        // KLUCZOWE: ignorujemy sklep — przekazujemy null dla $store
        [$w, $p] = $this->filters($from, $to, $hh, null, $category, $product);

        // Whitelist sortowania
        $sortable = [
            'sum'     => 'SUM(rs.amount)',
            'quantity'=> 'SUM(rs.quantity)',
            'name'    => 'p.name',
        ];
        $sortExpr = $sortable[$sort] ?? $sortable['sum'];
        $dir      = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $sql = "
            SELECT
                rs.product_id,
                p.name AS product_name,
                SUM(rs.quantity) AS total_quantity,
                SUM(rs.amount)   AS sum
            FROM report_spend rs
            JOIN product p ON p.id = rs.product_id
            $w
            GROUP BY rs.product_id, p.name
            ORDER BY $sortExpr $dir
            LIMIT :lim OFFSET :off
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($p as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('lim', (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue('off', (int)$offset, \PDO::PARAM_INT);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

}
