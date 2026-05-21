<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Optional customer mobile login via Google ID token (Block C).
 *
 * @see docs/BLOCK_C.md
 */
#[Route('/api/auth')]
class ApiGoogleAuthController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
        #[Autowire('%env(GOOGLE_CLIENT_ID)%')]
        private readonly string $googleClientId,
    ) {
    }

    #[Route('/google', name: 'api_auth_google', methods: ['POST'])]
    public function google(Request $request): JsonResponse
    {
        if ($this->googleClientId === '') {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Google OAuth is not configured (GOOGLE_CLIENT_ID).',
                ],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return $this->json(
                ['success' => false, 'errors' => ['body' => ['Invalid or empty JSON body.']]],
                Response::HTTP_BAD_REQUEST
            );
        }

        $idToken = trim((string) ($payload['idToken'] ?? ''));
        if ($idToken === '') {
            return $this->json(
                ['success' => false, 'errors' => ['idToken' => ['Missing idToken.']]],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $tokenInfoResponse = $this->httpClient->request(
                'GET',
                'https://oauth2.googleapis.com/tokeninfo',
                [
                    'query' => ['id_token' => $idToken],
                ]
            );
        } catch (\Throwable) {
            return $this->json(
                ['success' => false, 'message' => 'Could not reach Google to verify the token.'],
                Response::HTTP_BAD_GATEWAY
            );
        }

        if ($tokenInfoResponse->getStatusCode() !== Response::HTTP_OK) {
            return $this->json(
                ['success' => false, 'message' => 'Invalid Google ID token.'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        /** @var array<string, mixed> $google */
        $google = $tokenInfoResponse->toArray();

        $aud = (string) ($google['aud'] ?? '');
        if ($aud !== $this->googleClientId) {
            return $this->json(
                ['success' => false, 'message' => 'Token audience does not match GOOGLE_CLIENT_ID.'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $email = trim((string) ($google['email'] ?? ''));
        if ($email === '' || !$this->isGoogleEmailVerified($google)) {
            return $this->json(
                ['success' => false, 'message' => 'Google account email is missing or not verified.'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $name = trim((string) ($google['name'] ?? ''));
        if ($name === '') {
            $name = strstr($email, '@', true) ?: $email;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $random = bin2hex(random_bytes(24));
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $random));
            $user->setRole('ROLE_USER');
            $user->setIsVerified(true);
            $user->setVerificationToken(null);
            $user->setCreatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } else {
            if (!$user->isVerified()) {
                $user->setIsVerified(true);
                $user->setVerificationToken(null);
                $this->entityManager->flush();
            }
        }

        $jwt = $this->jwtTokenManager->create($user);

        return $this->json([
            'token' => $jwt,
            'user' => [
                'email' => $user->getEmail(),
                'name' => $user->getName(),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $google
     */
    private function isGoogleEmailVerified(array $google): bool
    {
        $v = $google['email_verified'] ?? false;

        return $v === true || $v === 'true';
    }
}
