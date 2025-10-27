<?php

namespace App\Controller;

use App\Service\ReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends AbstractController
{
    public function index(Request $req, ReportService $svc): Response
    {
        $from = $req->query->get('from');
        $to = $req->query->get('to');
        $hh = $req->query->get('household');
        return $this->render('reports/index.html.twig', [
            'sum' => $svc->sumByPeriod($from, $to, $hh),
            'byCat' => $svc->byCategory($from, $to, $hh),
            'byStore' => $svc->byStore($from, $to, $hh),
            'byProductTop' => $svc->byProductTop($from, $to, $hh, 10),
        ]);
    }
}
