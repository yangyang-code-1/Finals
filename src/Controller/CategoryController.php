<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/category')]
final class CategoryController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {}
    
    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        // Allow both admin and staff
        $this->denyAccessUnlessGranted('ROLE_USER'); // Everyone authenticated

        $user = $this->getUser();
        // Admin and staff should be able to see all categories; other users see only their own
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            $categories = $categoryRepository->findAll();
        } else {
            $categories = $categoryRepository->findBy(['createdBy' => $user]);
        }

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Allow both admin and staff
        $this->denyAccessUnlessGranted('ROLE_USER');

        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set the creator
            $user = $this->getUser();
            if ($user instanceof User) {
                $category->setCreatedBy($user);
            }
            
            $entityManager->persist($category);
            $entityManager->flush();

            // Log activity
            if ($user instanceof User) {
                $this->activityLogService->logCreate(
                    $user,
                    'Category',
                    $category->getId(),
                    "Created category: {$category->getName()}"
                );
            }

            $this->addFlash('success', 'Category created successfully.');

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        // Allow both admin and staff
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Authorization: Admins can edit any category. Staff may edit their own categories
        // and may also edit categories created by admins. Other users cannot edit.
        $user = $this->getUser();
        if ($user instanceof User) {
            if ($this->isGranted('ROLE_ADMIN')) {
                // admin allowed
            } elseif ($this->isGranted('ROLE_STAFF')) {
                $creator = $category->getCreatedBy();
                // allow if staff is the creator
                if ($creator !== $user) {
                    // allow if the creator is an admin
                    if (!$creator instanceof User || !in_array('ROLE_ADMIN', $creator->getRoles(), true)) {
                        $this->addFlash('error', 'You can only edit your own categories or categories created by admins.');
                        throw $this->createAccessDeniedException('You can only edit your own categories or categories created by admins.');
                    }
                }
            } else {
                $this->addFlash('error', 'You do not have permission to edit categories.');
                throw $this->createAccessDeniedException('You do not have permission to edit categories.');
            }
        } else {
            throw $this->createAccessDeniedException('You must be logged in to edit categories.');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Log activity
            if ($user instanceof User) {
                $this->activityLogService->logUpdate(
                    $user,
                    'Category',
                    $category->getId(),
                    "Updated category: {$category->getName()}"
                );
            }

            $this->addFlash('success', 'Category updated successfully.');

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        // Allow both admin and staff
        // Only admins may delete categories
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $categoryId = $category->getId();
            $categoryName = $category->getName();
            
            $entityManager->remove($category);
            $entityManager->flush();

            // Log activity
            if ($user instanceof User) {
                $this->activityLogService->logDelete(
                    $user,
                    'Category',
                    $categoryId,
                    "Deleted category: {$categoryName}"
                );
            }

            $this->addFlash('success', 'Category deleted successfully.');
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }
}