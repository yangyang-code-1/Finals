<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\CommissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommissionBrowseController extends AbstractController
{
    #[Route('/commissions', name: 'app_commissions_browse', methods: ['GET'])]
    public function browse(
        Request $request,
        CommissionRepository $commissionRepository,
        CategoryRepository $categoryRepository,
    ): Response {
        $q = trim((string) $request->query->get('q', ''));
        $categoryId = (int) $request->query->get('category', 0);

        $commissions = $commissionRepository->findForBrowse(
            $q !== '' ? $q : null,
            $categoryId > 0 ? $categoryId : null,
        );

        return $this->render('about/browse.html.twig', [
            'commissions' => $commissions,
            'categories' => $categoryRepository->findAll(),
            'q' => $q,
            'categoryId' => $categoryId,
        ]);
    }

    #[Route('/commissions/{id}/request', name: 'app_commission_request', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function requestCommission(
        Request $request,
        Commission $commission,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('info', 'Please log in or register before requesting a commission slot.');

            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('request_commission'.$commission->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid request token.');
        }

        $client = $commission->getClient();
        if ($client !== null && $client === $user) {
            $this->addFlash('info', 'You already requested this commission.');

            return $this->redirectToRoute('app_account_progress');
        }

        $commissionRequest = new Commission();
        $commissionRequest->setTitle($commission->getTitle() ?? 'Untitled commission');
        $commissionRequest->setDescription($commission->getDescription() ?? '');
        $commissionRequest->setPrice((float) $commission->getPrice());
        $commissionRequest->setCategory($commission->getCategory());
        $commissionRequest->setArtist($commission->getArtist());
        $commissionRequest->setClient($user);
        $commissionRequest->setStatus('Pending');

        $entityManager->persist($commissionRequest);
        $entityManager->flush();

        $this->addFlash('info', 'Commission requested. You can now track artist progress from your timeline.');

        return $this->redirectToRoute('app_account_progress');
    }
}

