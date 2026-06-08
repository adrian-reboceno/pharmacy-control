<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/ConfigureTwoFactor/ConfigureTwoFactorUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\ConfigureTwoFactor;

use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Contract\Service\TwoFactorServiceContract;
use PharmaControl\Auth\Domain\Exception\InvalidTotpCodeException;
use PharmaControl\Auth\Domain\ValueObject\TotpCode;
use PharmaControl\Auth\Domain\ValueObject\UserId;

final class ConfigureTwoFactorUseCase
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly TwoFactorServiceContract $twoFactor,
        private readonly EventPublisherContract $events,
    ) {}

    public function initiate(ConfigureTwoFactorCommand $command): array
    {
        $userId = new UserId($command->userId);
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new \RuntimeException('Usuario no encontrado.', 404);
        }

        $secret = $this->twoFactor->generateSecret();
        $qrUri = $this->twoFactor->generateQrCodeUri($secret, $user->email, 'PharmaControl');

        return [
            'secret' => $secret,
            'qr_uri' => $qrUri,
        ];
    }

    public function confirm(ConfigureTwoFactorCommand $command): array
    {
        $userId = new UserId($command->userId);
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new \RuntimeException('Usuario no encontrado.', 404);
        }

        $totpCode = new TotpCode($command->totpCode ?? '');

        if (! $this->twoFactor->verifyCode($command->secret ?? '', $totpCode)) {
            throw new InvalidTotpCodeException;
        }

        $user->enableTwoFactor($command->secret ?? '');
        $this->users->save($user);

        foreach ($user->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        $backupCodes = $this->twoFactor->generateBackupCodes();

        return ['backup_codes' => $backupCodes];
    }

    public function execute(ConfigureTwoFactorCommand $command): array
    {
        return $this->initiate($command);
    }
}
