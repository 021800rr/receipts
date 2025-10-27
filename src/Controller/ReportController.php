<?php

namespace App\Controller;

use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends AbstractController
{
    public function index(Request $req, ReportService $svc, EntityManagerInterface $em): Response
    {
        $from = $req->query->get('from');
        $to = $req->query->get('to');
        $hh = $req->query->get('household');
        $store = $req->query->get('store');
        $category = $req->query->get('category');
        $product = $req->query->get('product');

        $stores = $em->getRepository(\App\Entity\Store::class)->findAll();
        $categories = $em->getRepository(\App\Entity\Category::class)->findAll();
        $products = $em->getRepository(\App\Entity\Product::class)->findAll();

        return $this->render('reports/index.html.twig', [
            'sum' => $svc->sumByPeriod($from, $to, $hh, $store, $category, $product),
            'byCat' => $svc->byCategory($from, $to, $hh, $store, $category, $product),
            'byStore' => $svc->byStore($from, $to, $hh, $store, $category, $product),
            'byProductTop' => $svc->byProductTop($from, $to, $hh, 10, $store, $category, $product),
            'compare' => $svc->compareHouseholds($from, $to, $store, $category, $product),
            'stores' => $stores,
            'categories' => $categories,
            'products' => $products,
        ]);
    }
}
