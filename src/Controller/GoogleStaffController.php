<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GoogleStaffController extends AbstractController
{
    #[Route('/connect/google-staff', name: 'connect_google_staff')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('google')->redirect([
            'openid',
            'email',
            'profile',
        ], []);
    }

    #[Route('/connect/google-staff/check', name: 'connect_google_staff_check')]
    public function connectCheck(): Response
    {
        throw new \LogicException('This route is handled by GoogleStaffAuthenticator.');
    }
}
