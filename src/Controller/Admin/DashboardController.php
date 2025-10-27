<?php
namespace App\Controller\Admin;

use App\Entity\{Household,Store,Category,Product,Receipt};
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Dashboard, MenuItem};
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Paragony');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Panel', 'fa fa-home');

        yield MenuItem::section('Słowniki');
        yield MenuItem::linkToCrud('Gospodarstwa', 'fa fa-house', Household::class);
        yield MenuItem::linkToCrud('Sklepy',        'fa fa-store', Store::class);
        yield MenuItem::linkToCrud('Kategorie',     'fa fa-tags',  Category::class);
        yield MenuItem::linkToCrud('Produkty',      'fa fa-box',   Product::class);

        yield MenuItem::section('Paragony');
        yield MenuItem::linkToCrud('Paragony',      'fa fa-receipt', Receipt::class);
        // Jeśli chcesz jawnie, możesz dodać ->setController(ReceiptCrudController::class)
    }
}
