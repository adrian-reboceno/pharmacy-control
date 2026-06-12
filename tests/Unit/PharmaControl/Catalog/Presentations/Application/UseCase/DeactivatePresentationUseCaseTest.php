<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Presentations\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Presentations\Application\UseCase\DeactivatePresentation\DeactivatePresentationCommand;
use PharmaControl\Catalog\Presentations\Application\UseCase\DeactivatePresentation\DeactivatePresentationUseCase;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationDeactivated;
use PharmaControl\Catalog\Presentations\Domain\Exception\PresentationNotFoundException;
use PharmaControl\Catalog\Presentations\Domain\Model\Presentation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeactivatePresentationUseCaseTest extends TestCase
{
    private PresentationRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private DeactivatePresentationUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PresentationRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new DeactivatePresentationUseCase($this->repository, $this->events);
    }

    private function makePresentation(): Presentation
    {
        return Presentation::create(
            PresentationId::generate(),
            new PresentationName('Tableta'),
            new Abbreviation('Tab'),
            null,
            null,
        );
    }

    private function makeCommand(string $id): DeactivatePresentationCommand
    {
        return new DeactivatePresentationCommand($id, (string) UserId::generate());
    }

    public function test_deactivates_active_presentation(): void
    {
        $presentation = $this->makePresentation();
        $this->repository->method('findById')->willReturn($presentation);
        $this->repository->expects($this->once())->method('save');

        ($this->useCase)($this->makeCommand($presentation->getId()->value));

        self::assertFalse($presentation->isActive());
    }

    public function test_throws_presentation_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(PresentationNotFoundException::class);

        ($this->useCase)($this->makeCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function test_throws_domain_exception_when_already_inactive(): void
    {
        $presentation = $this->makePresentation();
        $presentation->releaseEvents();
        $presentation->deactivate();
        $presentation->releaseEvents();

        $this->repository->method('findById')->willReturn($presentation);

        $this->expectException(\DomainException::class);

        ($this->useCase)($this->makeCommand($presentation->getId()->value));
    }

    public function test_publishes_presentation_deactivated_event(): void
    {
        $presentation = $this->makePresentation();
        $this->repository->method('findById')->willReturn($presentation);

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(PresentationDeactivated::class));

        ($this->useCase)($this->makeCommand($presentation->getId()->value));
    }
}
