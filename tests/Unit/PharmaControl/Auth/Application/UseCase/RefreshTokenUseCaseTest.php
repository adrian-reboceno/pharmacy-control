<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Auth/Application/UseCase/RefreshTokenUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Auth\Application\UseCase;

use PharmaControl\Auth\Application\DTO\AuthTokenDTO;
use PharmaControl\Auth\Application\UseCase\RefreshToken\RefreshTokenCommand;
use PharmaControl\Auth\Application\UseCase\RefreshToken\RefreshTokenUseCase;
use PharmaControl\Auth\Domain\Contract\Repository\RoleRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\SessionRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Contract\Service\TokenServiceContract;
use PharmaControl\Auth\Domain\Model\UserSession;
use PharmaControl\Auth\Domain\ValueObject\ClientType;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\SessionId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RefreshTokenUseCaseTest extends TestCase
{
    private SessionRepositoryContract&MockObject $sessions;

    private RoleRepositoryContract&MockObject $roles;

    private TokenServiceContract&MockObject $tokens;

    private EventPublisherContract&MockObject $events;

    private RefreshTokenUseCase $useCase;

    protected function setUp(): void
    {
        $this->sessions = $this->createMock(SessionRepositoryContract::class);
        $this->roles = $this->createMock(RoleRepositoryContract::class);
        $this->tokens = $this->createMock(TokenServiceContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);

        $this->useCase = new RefreshTokenUseCase(
            $this->sessions,
            $this->roles,
            $this->tokens,
            $this->events,
        );
    }

    private function makeSession(string $refreshToken, bool $revoked = false, bool $expired = false): UserSession
    {
        $hash = hash('sha256', $refreshToken);
        $refreshExpiry = $expired
            ? (new \DateTimeImmutable)->modify('-1 day')
            : (new \DateTimeImmutable)->modify('+7 days');

        return UserSession::reconstitute(
            id: SessionId::generate(),
            userId: UserId::generate(),
            clientType: ClientType::WEB,
            accessTokenHash: hash('sha256', 'old_access'),
            refreshTokenHash: $hash,
            activeRoleId: RoleId::generate(),
            activeBranchId: null,
            accessExpiresAt: (new \DateTimeImmutable)->modify('+15 minutes'),
            refreshExpiresAt: $refreshExpiry,
            lastActivityAt: new \DateTimeImmutable,
            roleActivatedAt: new \DateTimeImmutable,
            ipAddress: new IpAddress('127.0.0.1'),
            userAgent: null,
            revokedAt: $revoked ? new \DateTimeImmutable : null,
        );
    }

    public function test_returns_new_token_pair_on_valid_refresh_token(): void
    {
        $refreshToken = 'valid_refresh_token_abc';
        $session = $this->makeSession($refreshToken);

        $this->sessions->method('findByRefreshTokenHash')->willReturn($session);
        $this->roles->method('resolvePermissions')->willReturn([]);
        $this->tokens->method('issue')->willReturn([
            'accessToken' => 'new.access.token',
            'refreshToken' => 'new_refresh_token',
            'expiresIn' => 900,
            'jti' => 'new-jti',
        ]);
        $this->sessions->expects($this->once())->method('save');

        $result = $this->useCase->execute(new RefreshTokenCommand($refreshToken, '1.1.1.1'));

        self::assertInstanceOf(AuthTokenDTO::class, $result);
        self::assertSame('new.access.token', $result->accessToken);
    }

    public function test_throws_when_session_is_revoked(): void
    {
        $refreshToken = 'revoked_refresh';
        $session = $this->makeSession($refreshToken, revoked: true);

        $this->sessions->method('findByRefreshTokenHash')->willReturn($session);

        $this->expectException(\RuntimeException::class);
        $this->useCase->execute(new RefreshTokenCommand($refreshToken, '1.1.1.1'));
    }

    public function test_throws_when_refresh_token_is_expired(): void
    {
        $refreshToken = 'expired_refresh';
        $session = $this->makeSession($refreshToken, expired: true);

        $this->sessions->method('findByRefreshTokenHash')->willReturn($session);

        $this->expectException(\RuntimeException::class);
        $this->useCase->execute(new RefreshTokenCommand($refreshToken, '1.1.1.1'));
    }

    public function test_throws_when_refresh_token_not_found(): void
    {
        $this->sessions->method('findByRefreshTokenHash')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->useCase->execute(new RefreshTokenCommand('unknown_token', '1.1.1.1'));
    }
}
