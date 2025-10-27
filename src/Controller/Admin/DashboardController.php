<?php

namespace App\Controller\Admin;

use App\Entity\Store;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Receipt;
use App\Entity\Household;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Paragony');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Słowniki');
        yield MenuItem::linkToCrud('Gospodarstwa', 'fa fa-home', Household::class);
        yield MenuItem::linkToCrud('Sklepy', 'fa fa-store', Store::class);
        yield MenuItem::linkToCrud('Kategorie', 'fa fa-list', Category::class);
        yield MenuItem::linkToCrud('Produkty', 'fa fa-tag', Product::class);

        yield MenuItem::section('Paragony');
        yield MenuItem::linkToCrud('Paragony', 'fa fa-receipt', Receipt::class);

        yield MenuItem::section('Raporty');
        yield MenuItem::linkToRoute('Raporty', 'fa fa-chart-column', 'reports');
    }

}
