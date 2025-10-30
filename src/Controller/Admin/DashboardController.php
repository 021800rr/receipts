<?php
namespace App\Controller\Admin;

use App\Entity\{Household,Store,Category,Product,Receipt};
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Dashboard, MenuItem};
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ReportService;
use Symfony\Component\Routing\Annotation\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    private EntityManagerInterface $em;
    private ReportService $svc;

    public function __construct(EntityManagerInterface $em, ReportService $svc)
    {
        $this->em = $em;
        $this->svc = $svc;
    }

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

        yield MenuItem::section('Raporty');
        yield MenuItem::linkToRoute('Raporty', 'fa fa-chart-bar', 'admin_reports');
    }

    #[Route('/admin/reports', name: 'admin_reports')]
    public function reportIndex(): Response
    {
        $req = $this->container->get('request_stack')->getCurrentRequest();
        $from = $req->query->get('from');
        $to = $req->query->get('to');
        $hh = $req->query->get('household');
        $store = $req->query->get('store');
        $category = $req->query->get('category');
        $product = $req->query->get('product');

        $stores = $this->em->getRepository(Store::class)->findAll();
        $categories = $this->em->getRepository(Category::class)->findAll();
        $products = $this->em->getRepository(Product::class)->findAll();
        $households = $this->em->getRepository(Household::class)->findAll();

        return $this->render('reports/index.html.twig', [
            'sum' => $this->svc->sumByPeriod($from, $to, $hh, $store, $category, $product),
            'byCat' => $this->svc->byCategory($from, $to, $hh, $store, $category, $product),
            'byStore' => $this->svc->byStore($from, $to, $hh, $store, $category, $product),
            'byProductTop' => $this->svc->byProductTop($from, $to, $hh, 10, $store, $category, $product),
            'compare' => $this->svc->compareHouseholds($from, $to, $store, $category, $product),
            'stores' => $stores,
            'categories' => $categories,
            'products' => $products,
            'households' => $households,
        ]);
    }
}
