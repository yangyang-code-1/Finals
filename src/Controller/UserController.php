<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/user')]
final class UserController extends AbstractController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private ActivityLogService $activityLogService
    ) {}

    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        // Only admins can view users
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get role filter from query parameter
        $roleFilter = $request->query->get('role');
        
        if ($roleFilter === 'ROLE_STAFF') {
            $users = $userRepository->findBy(['role' => 'ROLE_STAFF']);
        } elseif ($roleFilter === 'ROLE_USER') {
            $users = $userRepository->findBy(['role' => 'ROLE_USER']);
        } elseif ($roleFilter === 'ROLE_ADMIN') {
            $users = $userRepository->findBy(['role' => 'ROLE_ADMIN']);
        } else {
            $users = $userRepository->findAll();
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'role_filter' => $roleFilter,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Only admins can create users
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            // Set createdAt if not already set
            if (!$user->getCreatedAt()) {
                $user->setCreatedAt(new \DateTimeImmutable());
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // Log activity
            $currentUser = $this->getUser();
            if ($currentUser instanceof User) {
                $this->activityLogService->logCreate(
                    $currentUser,
                    'User',
                    $user->getId(),
                    "Created user: {$user->getName()} ({$user->getEmail()})"
                );
            }

            $this->addFlash('success', 'User created successfully.');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        // Only admins can view user details
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // Only admins can edit users
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password if a new one was provided
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            // Log activity
            $currentUser = $this->getUser();
            if ($currentUser instanceof User) {
                $this->activityLogService->logUpdate(
                    $currentUser,
                    'User',
                    $user->getId(),
                    "Updated user: {$user->getName()} ({$user->getEmail()})"
                );
            }

            $this->addFlash('success', 'User updated successfully.');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // Only admins can delete users
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $userId = $user->getId();
            $userName = $user->getName();
            $userEmail = $user->getEmail();
            
            $entityManager->remove($user);
            $entityManager->flush();

            // Log activity
            $currentUser = $this->getUser();
            if ($currentUser instanceof User) {
                $this->activityLogService->logDelete(
                    $currentUser,
                    'User',
                    $userId,
                    "Deleted user: {$userName} ({$userEmail})"
                );
            }

            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
