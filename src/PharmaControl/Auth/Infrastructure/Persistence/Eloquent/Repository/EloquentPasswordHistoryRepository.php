<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Persistence/Eloquent/Repository/EloquentPasswordHistoryRepository.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Auth\Domain\Contract\Repository\PasswordHistoryRepositoryContract;
use PharmaControl\Auth\Domain\ValueObject\HashedPassword;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentPasswordHistory;

final class EloquentPasswordHistoryRepository implements PasswordHistoryRepositoryContract
{
    public function save(UserId $userId, HashedPassword $hash): void
    {
        EloquentPasswordHistory::create([
            'id' => bin2hex(random_bytes(16)),
            'user_id' => $userId->value,
            'hash' => $hash->value,
            'created_at' => now(),
        ]);
    }

    /** @return list<HashedPassword> */
    public function findRecentByUser(UserId $userId, int $count): array
    {
        return EloquentPasswordHistory::where('user_id', $userId->value)
            ->orderByDesc('created_at')
            ->limit($count)
            ->get()
            ->map(fn ($m) => new HashedPassword($m->hash))
            ->values()
            ->all();
    }
}
