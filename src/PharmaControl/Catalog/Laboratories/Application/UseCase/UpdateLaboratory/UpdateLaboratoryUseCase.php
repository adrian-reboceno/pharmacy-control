<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Application/UseCase/UpdateLaboratory/UpdateLaboratoryUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Application\UseCase\UpdateLaboratory;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Laboratories\Application\DTO\LaboratoryDTO;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Domain\Exception\DuplicateLaboratoryNameException;
use PharmaControl\Catalog\Laboratories\Domain\Exception\LaboratoryNotFoundException;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\WebsiteUrl;

final class UpdateLaboratoryUseCase
{
    public function __construct(
        private readonly LaboratoryRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(UpdateLaboratoryCommand $command): LaboratoryDTO
    {
        $laboratory = $this->repository->findById(new LaboratoryId($command->id));
        if ($laboratory === null) {
            throw new LaboratoryNotFoundException($command->id);
        }

        $name = new LaboratoryName($command->name);
        $countryCode = new CountryCode($command->countryCode);
        $website = $command->website !== null ? new WebsiteUrl($command->website) : null;

        $existing = $this->repository->findByName($name);
        if ($existing !== null && ! $existing->getId()->equals($laboratory->getId())) {
            throw new DuplicateLaboratoryNameException($command->name);
        }

        $laboratory->update($name, $countryCode, $website);
        $this->repository->save($laboratory);

        foreach ($laboratory->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return LaboratoryDTO::fromDomain($laboratory);
    }
}
