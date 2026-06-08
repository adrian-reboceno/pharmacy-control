<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Contract/Service/TwoFactorServiceContract.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Contract\Service;

use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\TotpCode;

interface TwoFactorServiceContract
{
    public function generateSecret(): string;

    public function generateQrCodeUri(string $secret, Email $email, string $appName): string;

    public function verifyCode(string $secret, TotpCode $code): bool;

    /** @return list<string> */
    public function generateBackupCodes(): array;
}
