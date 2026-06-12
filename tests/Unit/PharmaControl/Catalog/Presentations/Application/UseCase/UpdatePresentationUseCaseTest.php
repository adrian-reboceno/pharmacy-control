<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Presentations\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Presentations\Application\DTO\PresentationDTO;
use PharmaControl\Catalog\Presentations\Application\UseCase\UpdatePresentation\UpdatePresentationCommand;
use PharmaControl\Catalog\Presentations\Application\UseCase\UpdatePresentation\UpdatePresentationUseCase;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationUpdated;
use PharmaControl\Catalog\Presentations\Domain\Exception\DuplicateAbbreviationException;
use PharmaControl\Catalog\Presentations\Domain\Exception\DuplicatePresentationNameException;
use PharmaControl\Catalog\Presentations\Domain\Exception\PresentationNotFoundException;
use PharmaControl\Catalog\Presentations\Domain\Model\Presentation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdatePresentationUseCaseTest extends TestCase
{
    private PresentationRepositoryContract&MockObject $repository;
    private EventPublisherContract&MockObject         $events;
    private UpdatePresentationUseCase                 $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PresentationRepositoryContract::class);
        $this->events     = $this->createMock(EventPublisherContract::class);
        $this->useCase    = new UpdatePresentationUseCase($this->repository, $this->events);
    }

    private function makePresentation(string $name = 'Tableta', string $abbreviation = 'Tab'): Presentation
    {
        return Presentation::create(
            PresentationId::generate(),
            new PresentationName($name),
            new Abbreviation($abbreviation),
            null,
            null,
        );
    }

    private function makeCommand(string $id, string $name = 'Cápsula', string $abbreviation = 'Cap'): UpdatePresentationCommand
    {
        return new UpdatePresentationCommand(
            id:           $id,
            name:         $name,
            abbreviation: $abbreviation,
            description:  null,
            actorUserId:  (string) UserId::generate(),
        );
    }

    public function test_updates_presentation_and_returns_dto(): void
    {
        $existing = $this->makePresentation();
        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByAbbreviation')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($existing->getId()->value));

        self::assertInstanceOf(PresentationDTO::class, $result);
        self::assertSame('Cápsula', $result->name);
        self::assertSame('Cap', $result->abbreviation);
    }

    public function test_throws_presentation_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(PresentationNotFoundException::class);

        ($this->useCase)($this->makeCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function test_throws_duplicate_presentation_name_exception_when_name_belongs_to_different_presentation(): void
    {
        $existing = $this->makePresentation('Tableta', 'Tab');
        $other    = $this->makePresentation('Cápsula', 'Cap');

        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn($other);
        $this->repository->method('findByAbbreviation')->willReturn(null);

        $this->expectException(DuplicatePresentationNameException::class);

        ($this->useCase)($this->makeCommand($existing->getId()->value, 'Cápsula', 'Tab'));
    }

    public function test_allows_keeping_same_name_on_own_presentation(): void
    {
        $existing = $this->makePresentation('Tableta', 'Tab');

        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn($existing);
        $this->repository->method('findByAbbreviation')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($existing->getId()->value, 'Tableta', 'Tab'));

        self::assertSame('Tableta', $result->name);
    }

    public function test_throws_duplicate_abbreviation_exception_when_abbreviation_belongs_to_different_presentation(): void
    {
        $existing = $this->makePresentation('Tableta', 'Tab');
        $other    = $this->makePresentation('Cápsula', 'Cap');

        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByAbbreviation')->willReturn($other);

        $this->expectException(DuplicateAbbreviationException::class);

        ($this->useCase)($this->makeCommand($existing->getId()->value, 'Tableta nueva', 'Cap'));
    }

    public function test_allows_keeping_same_abbreviation_on_own_presentation(): void
    {
        $existing = $this->makePresentation('Tableta', 'Tab');

        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByAbbreviation')->willReturn($existing);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($existing->getId()->value, 'Tableta nueva', 'Tab'));

        self::assertSame('Tab', $result->abbreviation);
    }

    public function test_publishes_presentation_updated_event(): void
    {
        $existing = $this->makePresentation();

        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByAbbreviation')->willReturn(null);

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(PresentationUpdated::class));

        ($this->useCase)($this->makeCommand($existing->getId()->value));
    }
}
