<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Model/User.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Model;

use PharmaControl\Auth\Domain\Event\AccountLocked;
use PharmaControl\Auth\Domain\Event\AccountUnlocked;
use PharmaControl\Auth\Domain\Event\LoginFailed;
use PharmaControl\Auth\Domain\Event\PasswordChanged;
use PharmaControl\Auth\Domain\Event\TwoFactorEnabled;
use PharmaControl\Auth\Domain\Event\UserDeactivated;
use PharmaControl\Auth\Domain\Event\UserRegistered;
use PharmaControl\Auth\Domain\Event\UserVerified;
use PharmaControl\Auth\Domain\Exception\AccountLockedException;
use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\HashedPassword;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Domain\ValueObject\UserStatus;
use PharmaControl\Shared\Event\DomainEvent;

final class User
{
    private UserStatus $status;

    private int $failedLoginAttempts;

    private ?\DateTimeImmutable $lockedUntil;

    private HashedPassword $passwordHash;

    private bool $mustChangePassword;

    private bool $twoFactorEnabled;

    private ?string $twoFactorSecret;

    private ?\DateTimeImmutable $passwordChangedAt;

    private ?\DateTimeImmutable $lastLoginAt;

    private ?\DateTimeImmutable $lastActivityAt;

    private ?\DateTimeImmutable $emailVerifiedAt;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        public readonly UserId $id,
        public readonly Email $email,
        HashedPassword $passwordHash,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $phone,
        UserStatus $status,
        bool $mustChangePassword,
        bool $twoFactorEnabled,
        ?string $twoFactorSecret,
        int $failedLoginAttempts,
        ?\DateTimeImmutable $lockedUntil,
        ?\DateTimeImmutable $passwordChangedAt,
        ?\DateTimeImmutable $lastLoginAt,
        ?\DateTimeImmutable $lastActivityAt,
        ?\DateTimeImmutable $emailVerifiedAt,
    ) {
        $this->passwordHash = $passwordHash;
        $this->status = $status;
        $this->mustChangePassword = $mustChangePassword;
        $this->twoFactorEnabled = $twoFactorEnabled;
        $this->twoFactorSecret = $twoFactorSecret;
        $this->failedLoginAttempts = $failedLoginAttempts;
        $this->lockedUntil = $lockedUntil;
        $this->passwordChangedAt = $passwordChangedAt;
        $this->lastLoginAt = $lastLoginAt;
        $this->lastActivityAt = $lastActivityAt;
        $this->emailVerifiedAt = $emailVerifiedAt;
    }

    public static function create(
        UserId $id,
        Email $email,
        HashedPassword $passwordHash,
        string $firstName,
        string $lastName,
        ?string $phone = null,
    ): self {
        $user = new self(
            id: $id,
            email: $email,
            passwordHash: $passwordHash,
            firstName: $firstName,
            lastName: $lastName,
            phone: $phone,
            status: UserStatus::PENDING_VERIFICATION,
            mustChangePassword: true,
            twoFactorEnabled: false,
            twoFactorSecret: null,
            failedLoginAttempts: 0,
            lockedUntil: null,
            passwordChangedAt: null,
            lastLoginAt: null,
            lastActivityAt: null,
            emailVerifiedAt: null,
        );

        $user->recordEvent(new UserRegistered($id, $email, new \DateTimeImmutable));

        return $user;
    }

    public static function reconstitute(
        UserId $id,
        Email $email,
        HashedPassword $passwordHash,
        string $firstName,
        string $lastName,
        ?string $phone,
        UserStatus $status,
        bool $mustChangePassword,
        bool $twoFactorEnabled,
        ?string $twoFactorSecret,
        int $failedLoginAttempts,
        ?\DateTimeImmutable $lockedUntil,
        ?\DateTimeImmutable $passwordChangedAt,
        ?\DateTimeImmutable $lastLoginAt,
        ?\DateTimeImmutable $lastActivityAt,
        ?\DateTimeImmutable $emailVerifiedAt,
    ): self {
        return new self(
            id: $id,
            email: $email,
            passwordHash: $passwordHash,
            firstName: $firstName,
            lastName: $lastName,
            phone: $phone,
            status: $status,
            mustChangePassword: $mustChangePassword,
            twoFactorEnabled: $twoFactorEnabled,
            twoFactorSecret: $twoFactorSecret,
            failedLoginAttempts: $failedLoginAttempts,
            lockedUntil: $lockedUntil,
            passwordChangedAt: $passwordChangedAt,
            lastLoginAt: $lastLoginAt,
            lastActivityAt: $lastActivityAt,
            emailVerifiedAt: $emailVerifiedAt,
        );
    }

    public function assertNotLocked(): void
    {
        if ($this->isLocked()) {
            throw new AccountLockedException($this->id, $this->lockedUntil);
        }
    }

    public function isLocked(): bool
    {
        return $this->lockedUntil !== null && $this->lockedUntil > new \DateTimeImmutable;
    }

    public function isPasswordExpired(int $rotationDays): bool
    {
        if ($this->passwordChangedAt === null) {
            return true;
        }
        $expiresAt = $this->passwordChangedAt->modify("+{$rotationDays} days");

        return $expiresAt < new \DateTimeImmutable;
    }

    public function incrementFailedAttempts(IpAddress $ip): void
    {
        $this->failedLoginAttempts++;
        $this->recordEvent(new LoginFailed(
            $this->email,
            $ip,
            $this->failedLoginAttempts,
            new \DateTimeImmutable,
        ));
    }

    public function lock(int $minutes, IpAddress $ip): void
    {
        if ($this->status === UserStatus::INACTIVE) {
            throw new \LogicException('No se puede bloquear una cuenta inactiva.');
        }
        $this->status = UserStatus::LOCKED;
        $this->lockedUntil = (new \DateTimeImmutable)->modify("+{$minutes} minutes");
        $this->recordEvent(new AccountLocked($this->id, $this->lockedUntil, $ip, new \DateTimeImmutable));
    }

    public function unlock(): void
    {
        $this->lockedUntil = null;
        $this->failedLoginAttempts = 0;
        $this->status = UserStatus::ACTIVE;
        $this->recordEvent(new AccountUnlocked($this->id, null, new \DateTimeImmutable));
    }

    public function resetFailedAttempts(): void
    {
        $this->failedLoginAttempts = 0;
    }

    public function changePassword(HashedPassword $newHash, ?UserId $changedBy = null, bool $forced = false): void
    {
        $this->passwordHash = $newHash;
        $this->passwordChangedAt = new \DateTimeImmutable;
        $this->mustChangePassword = false;
        $this->recordEvent(new PasswordChanged($this->id, $changedBy ?? $this->id, $forced, new \DateTimeImmutable));
    }

    public function enableTwoFactor(string $secret): void
    {
        $this->twoFactorEnabled = true;
        $this->twoFactorSecret = $secret;
        $this->recordEvent(new TwoFactorEnabled($this->id, new \DateTimeImmutable));
    }

    public function activate(): void
    {
        $this->status = UserStatus::ACTIVE;
        $this->emailVerifiedAt = new \DateTimeImmutable;
        $this->recordEvent(new UserVerified($this->id, $this->email, new \DateTimeImmutable));
    }

    public function deactivate(?UserId $deactivatedBy = null): void
    {
        $this->status = UserStatus::INACTIVE;
        $this->recordEvent(new UserDeactivated($this->id, $deactivatedBy, new \DateTimeImmutable));
    }

    public function recordLastLogin(): void
    {
        $this->lastLoginAt = new \DateTimeImmutable;
    }

    public function recordActivity(): void
    {
        $this->lastActivityAt = new \DateTimeImmutable;
    }

    public function markMustChangePassword(): void
    {
        $this->mustChangePassword = true;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function getFailedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    public function getLockedUntil(): ?\DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function mustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function getPasswordHash(): HashedPassword
    {
        return $this->passwordHash;
    }

    public function getPasswordChangedAt(): ?\DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function getLastActivityAt(): ?\DateTimeImmutable
    {
        return $this->lastActivityAt;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    /** @return list<DomainEvent> */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }
}
