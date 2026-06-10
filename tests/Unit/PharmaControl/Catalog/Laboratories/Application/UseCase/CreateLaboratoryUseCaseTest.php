<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Laboratories/Application/UseCase/CreateLaboratoryUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Laboratories\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Laboratories\Application\DTO\LaboratoryDTO;
use PharmaControl\Catalog\Laboratories\Application\UseCase\CreateLaboratory\CreateLaboratoryCommand;
use PharmaControl\Catalog\Laboratories\Application\UseCase\CreateLaboratory\CreateLaboratoryUseCase;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryCreated;
use PharmaControl\Catalog\Laboratories\Domain\Exception\DuplicateLaboratoryNameException;
use PharmaControl\Catalog\Laboratories\Domain\Model\Laboratory;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateLaboratoryUseCaseTest extends TestCase
{
    private LaboratoryRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private CreateLaboratoryUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LaboratoryRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new CreateLaboratoryUseCase($this->repository, $this->events);
    }

    private function makeCommand(string $name = 'Bayer'): CreateLaboratoryCommand
    {
        return new CreateLaboratoryCommand(
            name: $name,
            countryCode: 'DE',
            website: null,
            actorUserId: (string) UserId::generate(),
        );
    }

    public function test_creates_laboratory_and_returns_dto_on_success(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand());

        self::assertInstanceOf(LaboratoryDTO::class, $result);
        self::assertSame('Bayer', $result->name);
        self::assertSame('DE', $result->countryCode);
        self::assertTrue($result->isActive);
    }

    public function test_throws_duplicate_laboratory_name_exception_when_name_already_exists(): void
    {
        $existing = Laboratory::create(
            LaboratoryId::generate(),
            new LaboratoryName('Bayer'),
            new CountryCode('DE'),
            null,
            null,
        );

        $this->repository->method('findByName')->willReturn($existing);

        $this->expectException(DuplicateLaboratoryNameException::class);

        ($this->useCase)($this->makeCommand('Bayer'));
    }

    public function test_publishes_laboratory_created_event_on_success(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('save');

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(LaboratoryCreated::class));

        ($this->useCase)($this->makeCommand());
    }

    public function test_validates_vo_constraints_before_saving(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->useCase)(new CreateLaboratoryCommand(
            name: '',
            countryCode: 'DE',
            website: null,
            actorUserId: (string) UserId::generate(),
        ));
    }
}
