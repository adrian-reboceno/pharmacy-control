<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/UseCase/CreateUser/CreateUserUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\UseCase\CreateUser;

use PharmaControl\Auth\Application\DTO\UserDTO;
use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Contract\Service\NotificationServiceContract;
use PharmaControl\Auth\Domain\Model\User;
use PharmaControl\Auth\Domain\Policy\PasswordPolicy;
use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\UserId;

final class CreateUserUseCase
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly NotificationServiceContract $notifications,
        private readonly EventPublisherContract $events,
        private readonly PasswordPolicy $passwordPolicy,
    ) {}

    public function execute(CreateUserCommand $command): UserDTO
    {
        $email = new Email($command->email);

        if ($this->users->findByEmail($email) !== null) {
            throw new \RuntimeException("Ya existe un usuario con el email '{$email->value}'.", 409);
        }

        $temporaryPassword = $this->generateTemporaryPassword();
        $hashedPassword = $this->passwordPolicy->makeHash($temporaryPassword);

        $user = User::create(
            UserId::generate(),
            $email,
            $hashedPassword,
            $command->firstName,
            $command->lastName,
            $command->phone,
        );

        $this->users->save($user);

        $this->notifications->sendWelcomeEmail($email, $command->firstName, $temporaryPassword);

        foreach ($user->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return new UserDTO(
            id: $user->id->value,
            email: $user->email->value,
            firstName: $user->firstName,
            lastName: $user->lastName,
            phone: $user->phone,
            status: $user->getStatus()->value,
            twoFactorEnabled: $user->isTwoFactorEnabled(),
            mustChangePassword: $user->mustChangePassword(),
            lastLoginAt: null,
            emailVerifiedAt: null,
        );
    }

    private function generateTemporaryPassword(): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnpqrstuvwxyz';
        $numbers = '23456789';
        $special = '!@#$%^&*';
        $all = $upper.$lower.$numbers.$special;

        $password = $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        for ($i = 4; $i < 12; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }
}
