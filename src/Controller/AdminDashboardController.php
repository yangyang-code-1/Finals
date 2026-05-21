<?php
// src/Controller/AdminDashboardController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;

class AdminDashboardController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {}

    #[Route('/admin', name: 'app_admin_dashboard')]
    public function index(): Response
    {
        // Only admins can access the dashboard
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $commissionRepo = $this->doctrine->getRepository(\App\Entity\Commission::class);
        $categoryRepo = $this->doctrine->getRepository(\App\Entity\Category::class);
        $userRepo = $this->doctrine->getRepository(\App\Entity\User::class);
        $activityLogRepo = $this->doctrine->getRepository(\App\Entity\ActivityLog::class);

        $commissionCount = $commissionRepo->count([]);
        $categoryCount   = $categoryRepo->count([]);
        // Count users by role
        $userCount       = $userRepo->count(['role' => 'ROLE_USER']);
        $staffCount      = $userRepo->count(['role' => 'ROLE_STAFF']);
        $adminCount      = $userRepo->count(['role' => 'ROLE_ADMIN']);

        $allCommissions = $commissionRepo->findAll();
        $pendingCount = $commissionRepo->count(['status' => 'Pending']);
        $inProgressCount = $commissionRepo->count(['status' => 'In Progress']);
        $completedCount = $commissionRepo->count(['status' => 'Completed']);
        $pendingRequestCount = count($commissionRepo->findPendingRequests());
        $activeCommissionCount = count($commissionRepo->findActiveClientCommissions());
        $reservedCount = count(array_filter($allCommissions, static fn ($commission): bool => $commission->getClient() !== null));
        $totalCommissionValue = array_reduce(
            $allCommissions,
            static fn (float $sum, $commission): float => $sum + (float) $commission->getPrice(),
            0.0
        );

        $recentCommissions = $commissionRepo->findBy([], ['createdAt' => 'DESC'], 8);
        $recentActivities = $activityLogRepo->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('admin_dashboard/index.html.twig', [
            'commission_count'    => $commissionCount,
            'category_count'      => $categoryCount,
            'user_count'          => $userCount,
            'staff_count'         => $staffCount,
            'admin_count'         => $adminCount,
            'pending_count'       => $pendingCount,
            'pending_request_count' => $pendingRequestCount,
            'active_commission_count' => $activeCommissionCount,
            'in_progress_count'   => $inProgressCount,
            'completed_count'     => $completedCount,
            'reserved_count'      => $reservedCount,
            'total_commission_value' => $totalCommissionValue,
            'recent_commissions'  => $recentCommissions,
            'recent_activities'   => $recentActivities,
        ]);
    }
}