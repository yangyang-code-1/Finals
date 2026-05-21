<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VerifyController extends AbstractController
{
    #[Route('/verify/{token}', name: 'app_verify_email')]
    public function verify(string $token, EmailVerificationService $verificationService): Response
    {
        $user = $verificationService->verifyToken($token);

        if (!$user) {
            $this->addFlash('danger', 'Invalid or expired verification token.');
            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email has been verified! You can now log in.');
        return $this->redirectToRoute('app_login', ['verified' => '1']);
    }
}
