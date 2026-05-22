<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\User;
use App\Entity\Category;
use App\Form\CommissionType;
use App\Repository\CommissionRepository;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/commission')]
final class CommissionController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {}
    #[Route(name: 'app_commission_index', methods: ['GET'])]
    public function index(CommissionRepository $commissionRepository): Response
    {
        // Staff/Admin management view only (customers should use /commissions)
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        $pendingRequests = $commissionRepository->findPendingRequests();

        return $this->render('commission/index.html.twig', [
            'commissions' => $commissionRepository->findAll(),
            'pending_request_count' => count($pendingRequests),
            'active_commission_count' => count($commissionRepository->findActiveClientCommissions()),
        ]);
    }

    #[Route('/requests', name: 'app_commission_requests', methods: ['GET'])]
    public function requests(CommissionRepository $commissionRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        $requests = $commissionRepository->findPendingRequests();

        return $this->render('commission/requests.html.twig', [
            'requests' => $requests,
            'pending_request_count' => count($requests),
            'active_commission_count' => count($commissionRepository->findActiveClientCommissions()),
        ]);
    }

    #[Route('/active', name: 'app_commission_active', methods: ['GET'])]
    public function active(CommissionRepository $commissionRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        $activeCommissions = $commissionRepository->findActiveClientCommissions();

        return $this->render('commission/active.html.twig', [
            'active_commissions' => $activeCommissions,
            'active_commission_count' => count($activeCommissions),
            'pending_request_count' => count($commissionRepository->findPendingRequests()),
        ]);
    }

    #[Route('/{id}/accept', name: 'app_commission_accept_request', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function acceptRequest(
        Request $request,
        Commission $commission,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('accept_commission'.$commission->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid request token.');
        }

        if ($commission->getClient() === null) {
            $this->addFlash('danger', 'This commission has no client request to accept.');

            return $this->redirectToRoute('app_commission_requests');
        }

        $commission->setStatus('In Progress');
        $entityManager->flush();

        if ($user instanceof User) {
            $this->activityLogService->logUpdate(
                $user,
                'Commission',
                $commission->getId(),
                "Accepted commission request: {$commission->getTitle()}"
            );
        }

        $this->addFlash('success', 'Request accepted. The client timeline now shows In Progress.');

        return $this->redirectToRoute('app_commission_active');
    }

    #[Route('/{id}/complete', name: 'app_commission_complete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function complete(
        Request $request,
        Commission $commission,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('complete_commission'.$commission->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid request token.');
        }

        if ($commission->getClient() === null) {
            $this->addFlash('danger', 'Only requested client commissions can be marked completed here.');

            return $this->redirectToRoute('app_commission_active');
        }

        $commission->setStatus('Completed');
        $entityManager->flush();

        if ($user instanceof User) {
            $this->activityLogService->logUpdate(
                $user,
                'Commission',
                $commission->getId(),
                "Completed commission: {$commission->getTitle()}"
            );
        }

        $this->addFlash('success', 'Commission marked completed. The client timeline now shows Completed.');

        return $this->redirectToRoute('app_commission_active');
    }

    #[Route('/new', name: 'app_commission_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        // Only admins and staff can create commissions
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied. The user doesn\'t have ROLE_STAFF or ROLE_ADMIN.');
        }

        $commission = new Commission();
        $commission->setStatus('Pending');

        // Assign to current user if they're a staff member
        $user = $this->getUser();
        if ($user instanceof User) {
            $commission->setArtist($user);
        }

        $form = $this->createForm(CommissionType::class, $commission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commission);
            $entityManager->flush();

            // Log activity
            if ($user instanceof User) {
                $this->activityLogService->logCreate(
                    $user,
                    'Commission',
                    $commission->getId(),
                    "Created commission: {$commission->getTitle()}"
                );
            }

            return $this->redirectToRoute('app_commission_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('commission/new.html.twig', [
            'commission' => $commission,
            'form' => $form,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_commission_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Commission $commission): Response
    {
        // Staff/Admin management view only (customers should use /commissions)
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        return $this->render('commission/show.html.twig', [
            'commission' => $commission,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_commission_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Commission $commission, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        // Only admins and staff can edit commissions
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied. The user doesn\'t have ROLE_STAFF or ROLE_ADMIN.');
        }

        // Staff can only edit their own commissions
        $user = $this->getUser();
        // if ($user instanceof User && !$this->isGranted('ROLE_ADMIN')) {
        //     if ($commission->getArtist() !== $user) {
        //         throw $this->createAccessDeniedException('You can only edit your own commissions.');
        //     }
        // }

        $form = $this->createForm(CommissionType::class, $commission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Log activity
            if ($user instanceof User) {
                $this->activityLogService->logUpdate(
                    $user,
                    'Commission',
                    $commission->getId(),
                    "Updated commission: {$commission->getTitle()}"
                );
            }

            return $this->redirectToRoute('app_commission_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('commission/edit.html.twig', [
            'commission' => $commission,
            'form' => $form,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_commission_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Commission $commission, EntityManagerInterface $entityManager): Response
    {
        // Only admins can delete commissions (staff can only edit their own)
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$commission->getId(), $request->request->get('_token'))) {
            $commissionId = $commission->getId();
            $commissionTitle = $commission->getTitle();
            
            $entityManager->remove($commission);
            $entityManager->flush();

            // Log activity
            $user = $this->getUser();
            if ($user instanceof User) {
                $this->activityLogService->logDelete(
                    $user,
                    'Commission',
                    $commissionId,
                    "Deleted commission: {$commissionTitle}"
                );
            }
        }

        return $this->redirectToRoute('app_commission_index', [], Response::HTTP_SEE_OTHER);
    }
}
