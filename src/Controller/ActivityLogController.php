<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/activity-logs')]
class ActivityLogController extends AbstractController
{
    #[Route(name: 'app_activity_log_index', methods: ['GET'])]
    public function index(ActivityLogRepository $activityLogRepository, Request $request): Response
    {
        // Only admins can view activity logs
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get filter parameters
        $actionFilter = $request->query->get('action');
        $limit = (int) ($request->query->get('limit', 100));

        // Get logs based on filters
        if ($actionFilter) {
            $logs = $activityLogRepository->findByAction($actionFilter);
        } else {
            $logs = $activityLogRepository->findRecent($limit);
        }

        return $this->render('activity_log/index.html.twig', [
            'logs' => $logs,
            'action_filter' => $actionFilter,
        ]);
    }
}

