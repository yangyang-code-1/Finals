<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function log(
        ?User $user,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null
    ): void {
        $log = new ActivityLog();
        $log->setUser($user);
        
        // Get user's role
        if ($user) {
            $role = $user->getRole() ?? 'ROLE_USER';
        } else {
            $role = 'ANONYMOUS';
        }
        
        $log->setRole($role);
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setDescription($description);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function logLogin(User $user): void
    {
        $this->log($user, 'Login', null, null, "User {$user->getEmail()} logged in");
    }

    public function logLogout(?User $user): void
    {
        if ($user) {
            $this->log($user, 'Logout', null, null, "User {$user->getEmail()} logged out");
        }
    }

    public function logCreate(User $user, string $entityType, int $entityId, ?string $description = null): void
    {
        $this->log($user, 'Create', $entityType, $entityId, $description);
    }

    public function logUpdate(User $user, string $entityType, int $entityId, ?string $description = null): void
    {
        $this->log($user, 'Update', $entityType, $entityId, $description);
    }

    public function logDelete(User $user, string $entityType, int $entityId, ?string $description = null): void
    {
        $this->log($user, 'Delete', $entityType, $entityId, $description);
    }
}

