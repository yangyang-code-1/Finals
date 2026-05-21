<?php

namespace App\Security;

use App\Entity\User;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class GoogleStaffAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UrlGeneratorInterface $urlGenerator,
        private ActivityLogService $activityLogService,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_staff_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();
                if (!$email) {
                    throw new AuthenticationException('Google did not return an email address.');
                }

                $repo = $this->entityManager->getRepository(User::class);
                $user = $repo->findOneBy(['email' => $email]);

                if (!$user) {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setName($googleUser->getName() ?: strstr($email, '@', true) ?: 'Staff');
                    $user->setRole('ROLE_STAFF');
                    $user->setCreatedAt(new \DateTimeImmutable());
                    $plain = bin2hex(random_bytes(32));
                    $user->setPassword($this->passwordHasher->hashPassword($user, $plain));
                    $this->entityManager->persist($user);
                } else {
                    if ($user->getRole() !== 'ROLE_ADMIN') {
                        $user->setRole('ROLE_STAFF');
                    }
                    if (!$user->getName() && $googleUser->getName()) {
                        $user->setName($googleUser->getName());
                    }
                }

                $user->setIsVerified(true);
                $user->setVerificationToken(null);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $this->activityLogService->logLogin($user);
        }

        $roles = $user->getRoles();
        if (\in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
        }
        if (\in_array('ROLE_STAFF', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_commission_index'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->getFlashBag()->add(
            'danger',
            'Google sign-in failed. Please try again or use email and password.'
        );

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
