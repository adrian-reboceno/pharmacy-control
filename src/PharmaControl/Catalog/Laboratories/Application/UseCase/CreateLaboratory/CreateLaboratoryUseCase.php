<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Application/UseCase/CreateLaboratory/CreateLaboratoryUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Application\UseCase\CreateLaboratory;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Laboratories\Application\DTO\LaboratoryDTO;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Domain\Exception\DuplicateLaboratoryNameException;
use PharmaControl\Catalog\Laboratories\Domain\Model\Laboratory;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\WebsiteUrl;

final class CreateLaboratoryUseCase
{
    public function __construct(
        private readonly LaboratoryRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(CreateLaboratoryCommand $command): LaboratoryDTO
    {
        $name = new LaboratoryName($command->name);
        $countryCode = new CountryCode($command->countryCode);
        $website = $command->website !== null ? new WebsiteUrl($command->website) : null;

        if ($this->repository->findByName($name) !== null) {
            throw new DuplicateLaboratoryNameException($command->name);
        }

        $laboratory = Laboratory::create(
            LaboratoryId::generate(),
            $name,
            $countryCode,
            $website,
            new UserId($command->actorUserId),
        );

        $this->repository->save($laboratory);

        foreach ($laboratory->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return LaboratoryDTO::fromDomain($laboratory);
    }
}
