<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/admin/api/products')]
final class ProductApiController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository
    ) {}

    #[Route('', methods:['GET'])]
    public function search(Request $req): JsonResponse
    {
        $term = $req->query->get('term', '');
        $items = [];
        if ($term !== '') {
            $results = $this->productRepository->findByTerm($term);
        } else {
            $results = $this->productRepository->findBy([], null, 50);
        }
        foreach ($results as $p) {
            $items[] = ['id' => (string)$p->getId(), 'text' => $p->getName()];
        }
        return new JsonResponse(['items' => $items]);
    }

    #[Route('', methods:['POST'])]
    public function create(Request $req): JsonResponse
    {
        try {
            $data = json_decode($req->getContent(), true);
            $name = trim($data['name'] ?? '');
            if ($name === '') {
                return new JsonResponse(['error'=>'empty name'], Response::HTTP_BAD_REQUEST);
            }

            $name = preg_replace('/\s+/', ' ', $name);

            $existing = $this->productRepository->findOneBy(['name' => $name]);
            if ($existing) {
                return new JsonResponse(['id' => (string)$existing->getId(), 'text' => $existing->getName()], Response::HTTP_OK);
            }

            $categoryId = $data['categoryId'] ?? null;
            $categoryName = isset($data['categoryName']) ? trim($data['categoryName']) : null;

            if ($categoryId) {
                $category = $this->categoryRepository->find($categoryId);
                if (!$category) {
                    return new JsonResponse(['error' => 'category_not_found'], Response::HTTP_BAD_REQUEST);
                }
            } elseif ($categoryName && $categoryName !== '') {
                // find existing by name
                $category = $this->categoryRepository->findOneBy(['name' => $categoryName]);
                if (!$category) {
                    $category = new Category();
                    $category->setName($categoryName);
                    $this->categoryRepository->save($category, true);
                }
            } else {
                return new JsonResponse(['error' => 'category_required', 'message' => 'Provide categoryId or categoryName'], Response::HTTP_BAD_REQUEST);
            }

            $product = new Product();
            $product->setName($name);
            $product->setCategory($category);
            $this->productRepository->save($product, true);

            return new JsonResponse(['id' => (string)$product->getId(), 'text' => $product->getName()], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'server_error', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
