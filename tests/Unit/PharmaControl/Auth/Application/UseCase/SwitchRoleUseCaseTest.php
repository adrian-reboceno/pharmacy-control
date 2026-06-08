<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Auth/Application/UseCase/SwitchRoleUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Auth\Application\UseCase;

use PharmaControl\Auth\Application\DTO\AuthTokenDTO;
use PharmaControl\Auth\Application\UseCase\SwitchRole\SwitchRoleCommand;
use PharmaControl\Auth\Application\UseCase\SwitchRole\SwitchRoleUseCase;
use PharmaControl\Auth\Domain\Contract\Repository\RoleRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\SessionRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Contract\Service\TokenServiceContract;
use PharmaControl\Auth\Domain\Exception\BranchRequiredException;
use PharmaControl\Auth\Domain\Exception\UnauthorizedRoleException;
use PharmaControl\Auth\Domain\Model\Role;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\SessionId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SwitchRoleUseCaseTest extends TestCase
{
    private SessionRepositoryContract&MockObject $sessions;

    private RoleRepositoryContract&MockObject $roles;

    private TokenServiceContract&MockObject $tokens;

    private EventPublisherContract&MockObject $events;

    private SwitchRoleUseCase $useCase;

    protected function setUp(): void
    {
        $this->sessions = $this->createMock(SessionRepositoryContract::class);
        $this->roles = $this->createMock(RoleRepositoryContract::class);
        $this->tokens = $this->createMock(TokenServiceContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);

        $this->useCase = new SwitchRoleUseCase(
            $this->sessions,
            $this->roles,
            $this->tokens,
            $this->events,
        );
    }

    private function makeRole(string $name, int $level, bool $scoped = false): Role
    {
        return Role::create(RoleId::generate(), $name, ucfirst($name), $level, $scoped);
    }

    public function test_throws_unauthorized_role_exception_when_user_does_not_have_target_role(): void
    {
        $targetRole = $this->makeRole('branch-manager', 4);

        $this->roles->method('getCurrentRoles')->willReturn([]);

        $this->expectException(UnauthorizedRoleException::class);

        $this->useCase->execute(new SwitchRoleCommand(
            UserId::generate()->value,
            SessionId::generate()->value,
            $targetRole->id->value,
            null,
            'old-jti',
            900,
        ));
    }

    public function test_throws_branch_required_for_scoped_role_without_branch_id(): void
    {
        $targetRole = $this->makeRole('pharmacist', 6, true);

        $this->roles->method('getCurrentRoles')->willReturn([$targetRole]);
        $this->roles->method('findById')->willReturn($targetRole);

        $this->expectException(BranchRequiredException::class);

        $this->useCase->execute(new SwitchRoleCommand(
            UserId::generate()->value,
            SessionId::generate()->value,
            $targetRole->id->value,
            null,
            'old-jti',
            900,
        ));
    }

    public function test_issues_new_token_with_target_role_and_blacklists_current(): void
    {
        $targetRole = $this->makeRole('cashier', 8);

        $this->roles->method('getCurrentRoles')->willReturn([$targetRole]);
        $this->roles->method('findById')->willReturn($targetRole);
        $this->roles->method('resolvePermissions')->willReturn([]);
        $this->tokens->method('issue')->willReturn([
            'accessToken' => 'new.token',
            'refreshToken' => 'new_refresh',
            'expiresIn' => 900,
            'jti' => 'new-jti',
        ]);
        $this->tokens->expects($this->once())->method('blacklist');
        $this->sessions->method('findByAccessTokenHash')->willReturn(null);

        $result = $this->useCase->execute(new SwitchRoleCommand(
            UserId::generate()->value,
            SessionId::generate()->value,
            $targetRole->id->value,
            null,
            'old-jti',
            500,
        ));

        self::assertInstanceOf(AuthTokenDTO::class, $result);
        self::assertSame($targetRole->id->value, $result->activeRoleId);
    }

    public function test_publishes_role_switched_event(): void
    {
        $targetRole = $this->makeRole('cashier', 8);

        $this->roles->method('getCurrentRoles')->willReturn([$targetRole]);
        $this->roles->method('findById')->willReturn($targetRole);
        $this->roles->method('resolvePermissions')->willReturn([]);
        $this->tokens->method('issue')->willReturn([
            'accessToken' => 't', 'refreshToken' => 'r', 'expiresIn' => 900, 'jti' => 'j',
        ]);
        $this->sessions->method('findByAccessTokenHash')->willReturn(null);

        $this->useCase->execute(new SwitchRoleCommand(
            UserId::generate()->value,
            SessionId::generate()->value,
            $targetRole->id->value,
            null,
            'old-jti',
            500,
        ));

        $this->addToAssertionCount(1);
    }
}
