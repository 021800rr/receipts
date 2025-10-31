<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/admin/api/categories')]
final class CategoryApiController extends AbstractController
{
    public function __construct(private readonly CategoryRepository $categoryRepository) {}

    #[Route('', methods: ['GET'])]
    public function search(Request $req): JsonResponse
    {
        $term = (string) $req->query->get('term', '');
        $items = [];
        if ($term !== '') {
            $results = $this->categoryRepository->findByTerm($term);
        } else {
            $results = $this->categoryRepository->findBy([], null, 50);
        }
        foreach ($results as $c) {
            $items[] = ['id' => (string) $c->getId(), 'text' => $c->getName()];
        }

        return new JsonResponse(['items' => $items]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $req): JsonResponse
    {
        try {
            $data = json_decode($req->getContent(), true);
            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                return new JsonResponse(['error' => 'empty name'], Response::HTTP_BAD_REQUEST);
            }

            $name = preg_replace('/\s+/', ' ', $name);

            $existing = $this->categoryRepository->findOneBy(['name' => $name]);
            if ($existing) {
                return new JsonResponse(['id' => (string) $existing->getId(), 'text' => $existing->getName()], Response::HTTP_OK);
            }

            $category = new Category();
            $category->setName($name);
            $this->categoryRepository->save($category, true);

            return new JsonResponse(['id' => (string) $category->getId(), 'text' => $category->getName()], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'server_error', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

