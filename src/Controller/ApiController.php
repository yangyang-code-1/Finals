<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\CommissionRepository;
use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ApiController extends AbstractController
{
    private const COMMISSION_IMAGES = [
        'birthday_photoshoot.jpg',
        'Character_design.PNG',
        'icon.jpg',
        'illustration.png',
        'Portrait.JPG',
        'Sunset.jpg',
    ];

    /**
     * Standard envelope for mobile-friendly JSON (criterion 8).
     *
     * @param array<string, mixed> $data
     */
    private function jsonOk(array $data = [], ?string $message = null, int $status = Response::HTTP_OK): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data];
        if ($message !== null) {
            $payload['message'] = $message;
        }

        return new JsonResponse($payload, $status);
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function jsonError(array $errors, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'errors' => $errors,
        ], $status);
    }

    private function serializeCommission(Commission $commission, ?int $userId = null): array
    {
        $category = $commission->getCategory();
        $artist = $commission->getArtist();
        $client = $commission->getClient();
        $imageIndex = max(0, ((int) $commission->getId()) - 1) % count(self::COMMISSION_IMAGES);
        $imageFilename = self::COMMISSION_IMAGES[$imageIndex];

        return [
            'id' => $commission->getId(),
            'title' => $commission->getTitle(),
            'description' => $commission->getDescription(),
            'price' => $commission->getPrice(),
            'status' => $commission->getStatus(),
            'categoryName' => $category?->getName(),
            'artistName' => $artist?->getName() ?? 'Artist',
            'isAvailable' => $client === null,
            'isMine' => $client !== null && $client->getId() === $userId,
            'createdAt' => $commission->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $commission->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'imageFilename' => $imageFilename,
            'imagePath' => '/commissions_images/'.$imageFilename,
        ];
    }

    /**
     * Mobile API Endpoint 1: List Categories
     */
    #[Route('/categories', name: 'api_categories', methods: ['GET'])]
    public function getCategories(CategoryRepository $repository): JsonResponse
    {
        $categories = $repository->findAll();
        $data = array_map(fn ($c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
        ], $categories);

        return $this->jsonOk(['categories' => $data]);
    }

    /**
     * Mobile-safe commission list. This avoids the API Platform /api/commissions route.
     */
    #[Route('/mobile/commissions', name: 'api_mobile_commissions', methods: ['GET'])]
    public function getMobileCommissions(CommissionRepository $repository): JsonResponse
    {
        $user = $this->getUser();
        $userId = $user instanceof User ? $user->getId() : null;
        $data = array_map(
            fn (Commission $commission): array => $this->serializeCommission($commission, $userId),
            $repository->findForBrowse()
        );

        return $this->jsonOk(['commissions' => $data]);
    }

    /**
     * Mobile customer timeline: only commissions requested by the current customer.
     */
    #[Route('/mobile/progress', name: 'api_mobile_progress', methods: ['GET'])]
    public function getMobileProgress(CommissionRepository $repository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->jsonError(['auth' => ['Not authenticated.']], Response::HTTP_UNAUTHORIZED);
        }

        $data = array_map(
            fn (Commission $commission): array => $this->serializeCommission($commission, $user->getId()),
            $repository->findForClientProgress($user)
        );

        return $this->jsonOk(['commissions' => $data]);
    }

    #[Route('/mobile/commissions/{id}', name: 'api_mobile_commission_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getMobileCommission(int $id, CommissionRepository $repository): JsonResponse
    {
        $commission = $repository->find($id);
        if ($commission === null) {
            return $this->jsonError(['commission' => ['Commission not found.']], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        $userId = $user instanceof User ? $user->getId() : null;

        return $this->jsonOk(['commission' => $this->serializeCommission($commission, $userId)]);
    }

    #[Route('/mobile/commissions/{id}/request', name: 'api_mobile_commission_request', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function requestMobileCommission(
        int $id,
        CommissionRepository $repository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->jsonError(['auth' => ['Not authenticated.']], Response::HTTP_UNAUTHORIZED);
        }

        $commission = $repository->find($id);
        if ($commission === null) {
            return $this->jsonError(['commission' => ['Commission not found.']], Response::HTTP_NOT_FOUND);
        }

        $client = $commission->getClient();
        if ($client !== null && $client->getId() !== $user->getId()) {
            return $this->jsonError(
                ['commission' => ['That commission slot is already reserved by another client.']],
                Response::HTTP_CONFLICT
            );
        }

        if ($client === null) {
            $commission->setClient($user);
            $commission->setStatus('Pending');
        }

        $entityManager->flush();

        return $this->jsonOk(
            ['commission' => $this->serializeCommission($commission, $user->getId())],
            'Commission requested. You can now track it from My progress.'
        );
    }

    /**
     * Mobile API Endpoint 2: List Commissions
     */
    #[Route('/commissions', name: 'api_commissions', methods: ['GET'])]
    public function getCommissions(CommissionRepository $repository): JsonResponse
    {
        $commissions = $repository->findForBrowse();
        $user = $this->getUser();
        $userId = $user instanceof User ? $user->getId() : null;
        $data = array_map(function ($c) use ($userId) {
            $category = $c->getCategory();
            $artist = $c->getArtist();
            $client = $c->getClient();

            return [
                'id' => $c->getId(),
                'title' => $c->getTitle(),
                'description' => $c->getDescription(),
                'price' => $c->getPrice(),
                'status' => $c->getStatus(),
                'categoryName' => $category?->getName(),
                'artistName' => $artist?->getName() ?? 'Artist',
                'isAvailable' => $client === null,
                'isMine' => $client !== null && $client->getId() === $userId,
            ];
        }, $commissions);

        return $this->jsonOk(['commissions' => $data]);
    }

    /**
     * Mobile API Endpoint 3: Check API Status
     */
    #[Route('/status', name: 'api_status', methods: ['GET'])]
    public function getStatus(): JsonResponse
    {
        return $this->jsonOk([
            'status' => 'online',
            'version' => '1.0.0',
        ]);
    }

    /**
     * Mobile API: current authenticated customer profile.
     */
    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function getMe(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->jsonError(['auth' => ['Not authenticated.']], Response::HTTP_UNAUTHORIZED);
        }

        return $this->jsonOk([
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ],
        ]);
    }

    /**
     * Mobile API: single commission detail.
     */
    #[Route('/commissions/{id}', name: 'api_commission_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getCommission(int $id, CommissionRepository $repository): JsonResponse
    {
        $commission = $repository->find($id);
        if ($commission === null) {
            return $this->jsonError(['commission' => ['Commission not found.']], Response::HTTP_NOT_FOUND);
        }

        $category = $commission->getCategory();

        return $this->jsonOk([
            'commission' => [
                'id' => $commission->getId(),
                'title' => $commission->getTitle(),
                'description' => $commission->getDescription(),
                'price' => $commission->getPrice(),
                'status' => $commission->getStatus(),
                'categoryName' => $category?->getName(),
            ],
        ]);
    }

    /**
     * API registration — same verification flow as the web form (criterion 7).
     *
     * POST JSON body:
     * {
     *   "name": "Ada Lovelace",
     *   "email": "ada@example.com",
     *   "password": "minimum-6-chars",
     *   "agreeTerms": true
     * }
     */
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
        EmailVerificationService $emailVerificationService,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return $this->jsonError(['body' => ['Invalid or empty JSON body.']], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $plainPassword = (string) ($payload['password'] ?? '');
        $agreeTerms = filter_var($payload['agreeTerms'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $errors = [];

        if ($name === '') {
            $errors['name'][] = 'Please enter your name.';
        }
        if ($email === '') {
            $errors['email'][] = 'Please enter your email.';
        }
        if (strlen($plainPassword) < 6) {
            $errors['password'][] = 'Password must be at least 6 characters.';
        }
        if (!$agreeTerms) {
            $errors['agreeTerms'][] = 'You must agree to the terms.';
        }

        if ($errors !== []) {
            return $this->jsonError($errors, Response::HTTP_BAD_REQUEST);
        }

        if ($userRepository->findOneBy(['email' => $email])) {
            return $this->jsonError(['email' => ['There is already an account with this email.']], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

        $token = $emailVerificationService->generateVerificationToken();
        $user->setVerificationToken($token);
        $user->setIsVerified(false);
        $user->setRole('ROLE_USER');
        $user->setCreatedAt(new \DateTimeImmutable());

        $violations = $validator->validate($user);
        if (\count($violations) > 0) {
            foreach ($violations as $v) {
                $path = $v->getPropertyPath() ?: '_global';
                $errors[$path][] = $v->getMessage();
            }

            return $this->jsonError($errors, Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $verificationUrl = $urlGenerator->generate(
            'app_verify_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $emailVerificationService->sendVerificationEmail($user, $verificationUrl);

        return $this->jsonOk(
            [
                'user' => [
                    'email' => $user->getEmail(),
                    'verified' => $user->isVerified() ?? false,
                ],
                'verify' => [
                    'hint' => 'Use the link sent to your email (same as web registration).',
                ],
            ],
            'Registration successful. Check your email to verify your account.',
            Response::HTTP_CREATED
        );
    }
}
