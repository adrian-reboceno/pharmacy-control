<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Service/SanctumTokenService.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Service;

use Illuminate\Support\Facades\Redis;
use PharmaControl\Auth\Domain\Contract\Service\TokenServiceContract;
use PharmaControl\Auth\Domain\ValueObject\ClientType;
use PharmaControl\Auth\Domain\ValueObject\PermissionName;
use PharmaControl\Auth\Domain\ValueObject\RoleId;
use PharmaControl\Auth\Domain\ValueObject\SessionId;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\ValueObject\BranchId;

final class SanctumTokenService implements TokenServiceContract
{
    private const ACCESS_TTL = 900;   // 15 minutes

    private const REFRESH_TTL = 604800; // 7 days

    private string $secret;

    private string $roleLookupKey = 'token_role_';

    public function __construct()
    {
        $key = config('app.key', '');
        $this->secret = str_starts_with($key, 'base64:')
            ? base64_decode(substr($key, 7))
            : $key;
    }

    /** @param list<PermissionName> $permissions */
    public function issue(
        UserId $userId,
        RoleId $activeRoleId,
        ?BranchId $branchId,
        array $permissions,
        ClientType $clientType,
        SessionId $sessionId,
    ): array {
        $now = time();
        $jti = bin2hex(random_bytes(16));

        $roleName = Redis::get("role_name:{$activeRoleId->value}") ?? $activeRoleId->value;

        $payload = [
            'sub' => $userId->value,
            'jti' => $jti,
            'role_id' => $activeRoleId->value,
            'role' => $roleName,
            'branch_id' => $branchId?->value,
            'permissions' => array_map(fn (PermissionName $p) => $p->value, $permissions),
            'session_id' => $sessionId->value,
            'client_type' => $clientType->value,
            'iat' => $now,
            'exp' => $now + self::ACCESS_TTL,
        ];

        $accessToken = $this->encodeJwt($payload);
        $refreshToken = bin2hex(random_bytes(32));

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'expiresIn' => self::ACCESS_TTL,
            'jti' => $jti,
        ];
    }

    public function verify(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Token malformado.', 401);
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expectedSig = $this->base64UrlEncode(
            hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $this->secret, true)
        );

        if (! hash_equals($expectedSig, $signatureB64)) {
            throw new \RuntimeException('Firma del token inválida.', 401);
        }

        $payload = json_decode($this->base64UrlDecode($payloadB64), true);

        if ($payload === null || ! isset($payload['exp'])) {
            throw new \RuntimeException('Payload del token inválido.', 401);
        }

        if ($payload['exp'] < time()) {
            throw new \RuntimeException('Token expirado.', 401);
        }

        return $payload;
    }

    public function blacklist(string $jti, int $ttlSeconds): void
    {
        if ($ttlSeconds > 0) {
            Redis::setex("jwt_blacklist:{$jti}", $ttlSeconds, '1');
        }
    }

    public function isBlacklisted(string $jti): bool
    {
        return (bool) Redis::exists("jwt_blacklist:{$jti}");
    }

    private function encodeJwt(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$body}", $this->secret, true)
        );

        return "{$header}.{$body}.{$signature}";
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }
}
