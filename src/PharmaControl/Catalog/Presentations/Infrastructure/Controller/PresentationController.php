<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Infrastructure\Controller;

use PharmaControl\Catalog\Presentations\Application\DTO\PresentationDTO;
use PharmaControl\Catalog\Presentations\Application\UseCase\CreatePresentation\CreatePresentationCommand;
use PharmaControl\Catalog\Presentations\Application\UseCase\CreatePresentation\CreatePresentationUseCase;
use PharmaControl\Catalog\Presentations\Application\UseCase\DeactivatePresentation\DeactivatePresentationCommand;
use PharmaControl\Catalog\Presentations\Application\UseCase\DeactivatePresentation\DeactivatePresentationUseCase;
use PharmaControl\Catalog\Presentations\Application\UseCase\GetPresentations\GetPresentationsQuery;
use PharmaControl\Catalog\Presentations\Application\UseCase\GetPresentations\GetPresentationsUseCase;
use PharmaControl\Catalog\Presentations\Application\UseCase\UpdatePresentation\UpdatePresentationCommand;
use PharmaControl\Catalog\Presentations\Application\UseCase\UpdatePresentation\UpdatePresentationUseCase;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\Presentations\Domain\Exception\PresentationNotFoundException;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;

final class PresentationController
{
    public function __construct(
        private readonly CreatePresentationUseCase     $create,
        private readonly UpdatePresentationUseCase     $update,
        private readonly DeactivatePresentationUseCase $deactivate,
        private readonly GetPresentationsUseCase       $get,
        private readonly PresentationRepositoryContract $repository,
    ) {}

    public function index(array $data): array
    {
        return ($this->get)(new GetPresentationsQuery(
            search:   $data['search'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            perPage:  (int) ($data['per_page'] ?? 20),
            page:     (int) ($data['page'] ?? 1),
        ));
    }

    public function store(array $data): PresentationDTO
    {
        return ($this->create)(new CreatePresentationCommand(
            name:         $data['name'],
            abbreviation: $data['abbreviation'],
            description:  $data['description'] ?? null,
            actorUserId:  $data['actor_user_id'],
        ));
    }

    public function show(string $id): PresentationDTO
    {
        $presentation = $this->repository->findById(new PresentationId($id));
        if ($presentation === null) {
            throw new PresentationNotFoundException($id);
        }

        return PresentationDTO::fromDomain($presentation);
    }

    public function update(string $id, array $data): PresentationDTO
    {
        return ($this->update)(new UpdatePresentationCommand(
            id:           $id,
            name:         $data['name'],
            abbreviation: $data['abbreviation'],
            description:  $data['description'] ?? null,
            actorUserId:  $data['actor_user_id'],
        ));
    }

    public function destroy(string $id, string $actorUserId): void
    {
        ($this->deactivate)(new DeactivatePresentationCommand($id, $actorUserId));
    }
}
