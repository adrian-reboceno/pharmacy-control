<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/Login/LoginUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\Login;

use PharmaControl\Auth\Application\DTO\AuthTokenDTO;
use PharmaControl\Auth\Domain\Contract\Repository\AuditLogRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\RoleRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\SessionRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Contract\Service\NotificationServiceContract;
use PharmaControl\Auth\Domain\Contract\Service\RateLimiterServiceContract;
use PharmaControl\Auth\Domain\Contract\Service\TokenServiceContract;
use PharmaControl\Auth\Domain\Event\LoginSucceeded;
use PharmaControl\Auth\Domain\Exception\InvalidCredentialsException;
use PharmaControl\Auth\Domain\Model\AuditEntry;
use PharmaControl\Auth\Domain\Model\UserSession;
use PharmaControl\Auth\Domain\Policy\LockoutPolicy;
use PharmaControl\Auth\Domain\Policy\PasswordPolicy;
use PharmaControl\Auth\Domain\ValueObject\ClientType;
use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\SessionId;

final class LoginUseCase
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly SessionRepositoryContract $sessions,
        private readonly RoleRepositoryContract $roles,
        private readonly AuditLogRepositoryContract $auditLog,
        private readonly TokenServiceContract $tokens,
        private readonly RateLimiterServiceContract $rateLimiter,
        private readonly NotificationServiceContract $notifications,
        private readonly EventPublisherContract $events,
        private readonly LockoutPolicy $lockoutPolicy,
        private readonly PasswordPolicy $passwordPolicy,
    ) {}

    public function execute(LoginCommand $command): AuthTokenDTO
    {
        $ip = new IpAddress($command->ipAddress);
        $email = new Email($command->email);
        $clientType = ClientType::from($command->clientType);
        $rateLimitKey = "login:{$command->ipAddress}:{$command->email}";

        if ($this->rateLimiter->tooManyAttempts($rateLimitKey, 10)) {
            throw new \RuntimeException('Demasiados intentos. Por favor espere.', 429);
        }

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            $this->rateLimiter->hit($rateLimitKey, 10, 60);
            throw new InvalidCredentialsException;
        }

        $user->assertNotLocked();

        if (! $user->getPasswordHash()->verify($command->password)) {
            $this->rateLimiter->hit($rateLimitKey, 10, 60);
            $user->incrementFailedAttempts($ip);

            if ($this->lockoutPolicy->isThresholdReached($user->getFailedLoginAttempts())) {
                $user->lock($this->lockoutPolicy->calculateDuration(0), $ip);
                $this->users->save($user);

                foreach ($user->releaseEvents() as $event) {
                    $this->events->publish($event);
                }

                $this->notifications->sendLockNotification(
                    $user->id,
                    $user->email,
                    $ip,
                    $user->getLockedUntil()
                );
            } else {
                $this->users->save($user);
                foreach ($user->releaseEvents() as $event) {
                    $this->events->publish($event);
                }
            }

            throw new InvalidCredentialsException;
        }

        $user->resetFailedAttempts();
        $user->recordLastLogin();
        $this->rateLimiter->clear($rateLimitKey);

        $userRoles = $this->roles->getCurrentRoles($user->id);

        if ($user->mustChangePassword()) {
            $this->users->save($user);
            foreach ($user->releaseEvents() as $event) {
                $this->events->publish($event);
            }

            return new AuthTokenDTO(
                accessToken: '',
                refreshToken: '',
                expiresIn: 0,
                tokenType: 'Bearer',
                requiresPasswordChange: true,
                requiresRoleSelection: false,
                availableRoles: null,
                activeRoleId: null,
                activeBranchId: null,
            );
        }

        if (count($userRoles) > 1) {
            $this->users->save($user);
            foreach ($user->releaseEvents() as $event) {
                $this->events->publish($event);
            }

            return new AuthTokenDTO(
                accessToken: '',
                refreshToken: '',
                expiresIn: 0,
                tokenType: 'Bearer',
                requiresPasswordChange: false,
                requiresRoleSelection: true,
                availableRoles: array_map(fn ($r) => [
                    'id' => $r->id->value,
                    'name' => $r->name,
                    'display_name' => $r->displayName,
                    'branch_scoped' => $r->branchScoped,
                ], $userRoles),
                activeRoleId: null,
                activeBranchId: null,
            );
        }

        $activeRole = $userRoles[0];

        if ($this->passwordPolicy->isExpired($user, $activeRole)) {
            $user->markMustChangePassword();
            $this->users->save($user);
            foreach ($user->releaseEvents() as $event) {
                $this->events->publish($event);
            }

            return new AuthTokenDTO(
                accessToken: '',
                refreshToken: '',
                expiresIn: 0,
                tokenType: 'Bearer',
                requiresPasswordChange: true,
                requiresRoleSelection: false,
                availableRoles: null,
                activeRoleId: null,
                activeBranchId: null,
            );
        }

        $permissions = $this->roles->resolvePermissions($activeRole->id);
        $sessionId = SessionId::generate();

        $tokenData = $this->tokens->issue(
            $user->id,
            $activeRole->id,
            null,
            $permissions,
            $clientType,
            $sessionId,
            $user->getFirstName(),
            $user->getLastName(),
            $user->email->value
        );

        $accessHash = hash('sha256', $tokenData['accessToken']);
        $refreshHash = hash('sha256', $tokenData['refreshToken']);

        $session = UserSession::create(
            id: $sessionId,
            userId: $user->id,
            clientType: $clientType,
            accessTokenHash: $accessHash,
            refreshTokenHash: $refreshHash,
            activeRoleId: $activeRole->id,
            activeBranchId: null,
            accessExpiresAt: (new \DateTimeImmutable)->modify("+{$tokenData['expiresIn']} seconds"),
            refreshExpiresAt: (new \DateTimeImmutable)->modify('+7 days'),
            ipAddress: $ip,
            userAgent: $command->userAgent,
        );

        $this->sessions->save($session);
        $this->users->save($user);

        $this->events->publish(new LoginSucceeded($user->id, $clientType, $ip, new \DateTimeImmutable));

        foreach ($user->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        $this->auditLog->append(AuditEntry::createLoginSuccess(
            $user->id,
            $user->email->value,
            $activeRole->name,
            $ip,
            $command->userAgent,
        ));

        return new AuthTokenDTO(
            accessToken: $tokenData['accessToken'],
            refreshToken: $tokenData['refreshToken'],
            expiresIn: $tokenData['expiresIn'],
            tokenType: 'Bearer',
            requiresPasswordChange: false,
            requiresRoleSelection: false,
            availableRoles: null,
            activeRoleId: $activeRole->id->value,
            activeBranchId: null,
        );
    }
}
