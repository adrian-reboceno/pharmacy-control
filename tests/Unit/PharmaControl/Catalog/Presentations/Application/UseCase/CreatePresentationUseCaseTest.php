<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Presentations\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Presentations\Application\DTO\PresentationDTO;
use PharmaControl\Catalog\Presentations\Application\UseCase\CreatePresentation\CreatePresentationCommand;
use PharmaControl\Catalog\Presentations\Application\UseCase\CreatePresentation\CreatePresentationUseCase;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationCreated;
use PharmaControl\Catalog\Presentations\Domain\Exception\DuplicateAbbreviationException;
use PharmaControl\Catalog\Presentations\Domain\Exception\DuplicatePresentationNameException;
use PharmaControl\Catalog\Presentations\Domain\Model\Presentation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreatePresentationUseCaseTest extends TestCase
{
    private PresentationRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private CreatePresentationUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PresentationRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new CreatePresentationUseCase($this->repository, $this->events);
    }

    private function makeCommand(string $name = 'Tableta', string $abbreviation = 'Tab'): CreatePresentationCommand
    {
        return new CreatePresentationCommand(
            name: $name,
            abbreviation: $abbreviation,
            description: 'Forma sólida oral.',
            actorUserId: (string) UserId::generate(),
        );
    }

    public function test_creates_presentation_and_returns_dto_on_success(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByAbbreviation')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand());

        self::assertInstanceOf(PresentationDTO::class, $result);
        self::assertSame('Tableta', $result->name);
        self::assertSame('Tab', $result->abbreviation);
        self::assertTrue($result->isActive);
    }

    public function test_throws_duplicate_presentation_name_exception_when_name_already_exists_case_insensitive(): void
    {
        $existing = Presentation::create(
            PresentationId::generate(),
            new PresentationName('TABLETA'),
            new Abbreviation('TAB'),
            null,
            null,
        );

        $this->repository->method('findByName')->willReturn($existing);
        $this->repository->method('findByAbbreviation')->willReturn(null);

        $this->expectException(DuplicatePresentationNameException::class);

        ($this->useCase)($this->makeCommand('Tableta'));
    }

    public function test_throws_duplicate_abbreviation_exception_when_abbreviation_already_exists_case_insensitive(): void
    {
        $existing = Presentation::create(
            PresentationId::generate(),
            new PresentationName('Tableta recubierta'),
            new Abbreviation('TAB'),
            null,
            null,
        );

        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByAbbreviation')->willReturn($existing);

        $this->expectException(DuplicateAbbreviationException::class);

        ($this->useCase)($this->makeCommand('Tableta', 'Tab'));
    }

    public function test_publishes_presentation_created_event(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByAbbreviation')->willReturn(null);

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(PresentationCreated::class));

        ($this->useCase)($this->makeCommand());
    }
}
