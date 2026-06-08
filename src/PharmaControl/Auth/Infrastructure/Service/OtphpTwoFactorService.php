<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Service/OtphpTwoFactorService.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Service;

use PharmaControl\Auth\Domain\Contract\Service\TwoFactorServiceContract;
use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\TotpCode;

final class OtphpTwoFactorService implements TwoFactorServiceContract
{
    private const DIGITS = 6;

    private const PERIOD = 30;

    private const WINDOW = 1;

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(): string
    {
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= self::ALPHABET[random_int(0, 31)];
        }

        return $secret;
    }

    public function generateQrCodeUri(string $secret, Email $email, string $appName): string
    {
        $label = rawurlencode($appName).':'.rawurlencode($email->value);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $appName,
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$params}";
    }

    public function verifyCode(string $secret, TotpCode $code): bool
    {
        $timestamp = (int) floor(time() / self::PERIOD);

        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if ($this->computeTotp($secret, $timestamp + $i) === $code->value) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = sprintf('%04d-%04d', random_int(0, 9999), random_int(0, 9999));
        }

        return $codes;
    }

    private function computeTotp(string $secret, int $timestamp): string
    {
        $key = $this->base32Decode($secret);
        $counter = pack('N*', 0).pack('N*', $timestamp);
        $hash = hash_hmac('sha1', $counter, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $input): string
    {
        $input = strtoupper(trim($input, '='));
        $chars = self::ALPHABET;
        $output = '';
        $buffer = 0;
        $bits = 0;

        foreach (str_split($input) as $char) {
            $pos = strpos($chars, $char);
            if ($pos === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $pos;
            $bits += 5;
            if ($bits >= 8) {
                $output .= chr(($buffer >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }

        return $output;
    }
}
