<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Service/LaravelQueueEventPublisher.php ──

declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Service;

use Illuminate\Support\Facades\Event;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Event\AccountLocked;
use PharmaControl\Auth\Domain\Event\AccountUnlocked;
use PharmaControl\Auth\Domain\Event\LoginFailed;
use PharmaControl\Auth\Domain\Event\LoginSucceeded;
use PharmaControl\Auth\Domain\Event\PasswordChanged;
use PharmaControl\Auth\Domain\Event\RoleAssigned;
use PharmaControl\Auth\Domain\Event\RoleRevoked;
use PharmaControl\Auth\Domain\Event\RoleSwitched;
use PharmaControl\Auth\Domain\Event\SessionRevoked;
use PharmaControl\Auth\Domain\Event\UserRegistered;
use PharmaControl\Auth\Infrastructure\Job\NotifyAdminOnLockoutJob;
use PharmaControl\Shared\Event\DomainEvent;

final class LaravelQueueEventPublisher implements EventPublisherContract
{
    public function publish(DomainEvent $event): void
    {
        // FIX: además de la lógica manual específica de Auth (abajo), todo
        // DomainEvent —de CUALQUIER módulo— se reenvía al Event Bus nativo
        // de Laravel. Esto es lo que permite que listeners registrados en
        // EventServiceProvider::$listen (como SyncLaboratoryListener,
        // SyncCategoryListener, etc. de TODOS los catálogos) reciban el
        // evento. Antes de este fix, cualquier evento fuera de los conocidos
        // por el match() de abajo cumplía el 'default => null' y se perdía
        // silenciosamente — nunca llegaba a Horizon, nunca fallaba, nunca
        // se completaba, simplemente desaparecía.
        Event::dispatch($event);

        // Lógica específica de Auth — se mantiene exactamente igual.
        match (true) {
            $event instanceof AccountLocked => $this->onAccountLocked($event),
            $event instanceof AccountUnlocked => $this->onAccountUnlocked($event),
            $event instanceof LoginFailed => $this->onLoginFailed($event),
            $event instanceof LoginSucceeded => $this->onLoginSucceeded($event),
            $event instanceof UserRegistered => $this->onUserRegistered($event),
            $event instanceof PasswordChanged => $this->onPasswordChanged($event),
            $event instanceof RoleAssigned => $this->onRoleAssigned($event),
            $event instanceof RoleRevoked => $this->onRoleRevoked($event),
            $event instanceof RoleSwitched => $this->onRoleSwitched($event),
            $event instanceof SessionRevoked => $this->onSessionRevoked($event),
            default => null,
        };
    }

    private function onAccountLocked(AccountLocked $event): void
    {
        NotifyAdminOnLockoutJob::dispatch(
            $event->userId->value,
            $event->ipAddress->value,
            $event->lockedUntil->format('Y-m-d H:i:s'),
        );
    }

    private function onAccountUnlocked(AccountUnlocked $event): void {}
    private function onLoginFailed(LoginFailed $event): void {}
    private function onLoginSucceeded(LoginSucceeded $event): void {}
    private function onUserRegistered(UserRegistered $event): void {}
    private function onPasswordChanged(PasswordChanged $event): void {}
    private function onRoleAssigned(RoleAssigned $event): void {}
    private function onRoleRevoked(RoleRevoked $event): void {}
    private function onRoleSwitched(RoleSwitched $event): void {}
    private function onSessionRevoked(SessionRevoked $event): void {}
}