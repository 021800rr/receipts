<?php
namespace App\Controller\Admin;

use App\Repository\CategoryRepository;
use App\Repository\HouseholdRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use App\Entity\{Household,Store,Category,Product,Receipt};
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Dashboard, MenuItem};
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ReportService;
use Symfony\Component\Routing\Attribute\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly StoreRepository $storeRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly HouseholdRepository $householdRepository,
    ) {}

    public function index(): Response
    {
        return $this->redirect($this->generateUrl('admin_receipt_new'));
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Paragony');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Słowniki');
        yield MenuItem::linkToCrud('Gospodarstwa', 'fa fa-house', Household::class);
        yield MenuItem::linkToCrud('Sklepy',        'fa fa-store', Store::class);
        yield MenuItem::linkToCrud('Kategorie',     'fa fa-tags',  Category::class);
        yield MenuItem::linkToCrud('Produkty',      'fa fa-box',   Product::class);

        yield MenuItem::section('Paragony');
        yield MenuItem::linkToCrud('Paragony',      'fa fa-receipt', Receipt::class);

        yield MenuItem::section('Raporty');
        yield MenuItem::linkToRoute('Raport', 'fa fa-chart-bar', 'admin_reports');
    }

    #[Route('/admin/reports', name: 'admin_reports')]
    public function reportIndex(): Response
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $from = $request->query->get('from');
        $to = $request->query->get('to');
        $hh = $request->query->get('household');
        $store = $request->query->get('store');
        $category = $request->query->get('category');
        $product = $request->query->get('product');

        $stores = $this->storeRepository->findAll();
        $categories = $this->categoryRepository->findAll();
        $products = $this->productRepository->findAll();
        $households = $this->householdRepository->findAll();

        return $this->render('reports/index.html.twig', [
            'sum' => $this->reportService->sumByPeriod($from, $to, $hh, $store, $category, $product),
            'byCat' => $this->reportService->byCategory($from, $to, $hh, $store, $category, $product),
            'byStore' => $this->reportService->byStore($from, $to, $hh, $store, $category, $product),
            'byProductTop' => $this->reportService->byProductTop($from, $to, $hh, 10, $store, $category, $product),
            'compare' => $this->reportService->compareHouseholds($from, $to, $store, $category, $product),
            'stores' => $stores,
            'categories' => $categories,
            'products' => $products,
            'households' => $households,
        ]);
    }
}
