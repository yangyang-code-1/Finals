<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CommissionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function profile(CommissionRepository $commissionRepository): Response
    {
        $user = $this->getUser();
        $clientCommissions = $user instanceof User ? $commissionRepository->findForClientProgress($user) : [];
        $artistCommissions = $user instanceof User ? $commissionRepository->findForArtist($user) : [];
        $completedCommissions = array_filter($clientCommissions, static fn ($commission): bool => $commission->getStatus() === 'Completed');

        return $this->render('account/profile.html.twig', [
            'client_commissions' => $clientCommissions,
            'artist_commissions' => $artistCommissions,
            'completed_commissions_count' => count($completedCommissions),
        ]);
    }

    #[Route('/account/progress', name: 'app_account_progress')]
    public function progress(CommissionRepository $commissionRepository): Response
    {
        $user = $this->getUser();
        $commissions = $user instanceof User ? $commissionRepository->findForClientProgress($user) : [];

        return $this->render('account/progress.html.twig', [
            'commissions' => $commissions,
            'timeline_steps' => ['Pending', 'In Progress', 'Completed'],
        ]);
    }

    /** Old demo URL — permanent redirect for bookmarks and external links. */
    #[Route('/sample', name: 'app_sample')]
    public function legacySampleRedirect(): Response
    {
        return $this->redirectToRoute('app_account', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
