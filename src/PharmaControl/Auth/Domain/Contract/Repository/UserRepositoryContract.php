<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Contract/Repository/UserRepositoryContract.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Contract\Repository;

use PharmaControl\Auth\Domain\Model\User;
use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Domain\ValueObject\UserStatus;

interface UserRepositoryContract
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    /** @return list<User> */
    public function findByStatus(UserStatus $status): array;

    public function delete(UserId $id): void;
}
