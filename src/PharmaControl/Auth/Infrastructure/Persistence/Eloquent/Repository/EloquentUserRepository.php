<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Persistence/Eloquent/Repository/EloquentUserRepository.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Model\User;
use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Domain\ValueObject\UserStatus;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;
use PharmaControl\Auth\Infrastructure\Persistence\Mapper\UserMapper;

final class EloquentUserRepository implements UserRepositoryContract
{
    public function __construct(private readonly UserMapper $mapper) {}

    public function save(User $user): void
    {
        $data = $this->mapper->toPersistence($user);
        EloquentUser::withoutGlobalScopes()->updateOrCreate(
            ['id' => $data['id']],
            $data
        );
    }

    public function findById(UserId $id): ?User
    {
        $model = EloquentUser::withoutGlobalScopes()->find($id->value);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $model = EloquentUser::withoutGlobalScopes()
            ->where('email', $email->value)
            ->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    /** @return list<User> */
    public function findByStatus(UserStatus $status): array
    {
        return EloquentUser::withoutGlobalScopes()
            ->where('status', $status->value)
            ->get()
            ->map(fn (EloquentUser $m) => $this->mapper->toDomain($m))
            ->values()
            ->all();
    }

    public function delete(UserId $id): void
    {
        EloquentUser::withoutGlobalScopes()
            ->where('id', $id->value)
            ->delete();
    }
}
