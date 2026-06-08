<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Service/LaravelNotificationService.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Service;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PharmaControl\Auth\Domain\Contract\Service\NotificationServiceContract;
use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\UserId;

final class LaravelNotificationService implements NotificationServiceContract
{
    public function sendLockNotification(
        UserId $userId,
        Email $email,
        IpAddress $ip,
        \DateTimeImmutable $lockedUntil,
    ): void {
        try {
            Mail::raw(
                "La cuenta {$email->value} ha sido bloqueada hasta {$lockedUntil->format('Y-m-d H:i:s')} "
                ."por múltiples intentos fallidos desde {$ip->value}.",
                function ($message) use ($email) {
                    $adminEmail = config('mail.admin_address', 'admin@pharmacontrol.local');
                    $message->to($adminEmail)
                        ->subject('[PharmaControl] Cuenta bloqueada: '.$email->value);
                }
            );
        } catch (\Throwable $e) {
            Log::error('Fallo al enviar notificación de bloqueo', [
                'email' => $email->value,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function sendUnlockEmail(Email $email): void
    {
        try {
            Mail::raw(
                'Su cuenta en PharmaControl ha sido desbloqueada. Ya puede iniciar sesión.',
                function ($message) use ($email) {
                    $message->to($email->value)
                        ->subject('[PharmaControl] Cuenta desbloqueada');
                }
            );
        } catch (\Throwable $e) {
            Log::error('Fallo al enviar email de desbloqueo', [
                'email' => $email->value,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function sendWelcomeEmail(Email $email, string $firstName, string $temporaryPassword): void
    {
        try {
            Mail::raw(
                "Bienvenido/a a PharmaControl, {$firstName}.\n\n"
                ."Su contraseña temporal es: {$temporaryPassword}\n\n"
                .'Por seguridad, deberá cambiarla en su primer inicio de sesión.',
                function ($message) use ($email, $firstName) {
                    $message->to($email->value, $firstName)
                        ->subject('[PharmaControl] Bienvenido/a — Acceso inicial');
                }
            );
        } catch (\Throwable $e) {
            Log::error('Fallo al enviar email de bienvenida', [
                'email' => $email->value,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function sendPasswordExpiredWarning(Email $email): void
    {
        try {
            Mail::raw(
                'Su contraseña en PharmaControl ha expirado. Por favor inicie sesión para actualizarla.',
                function ($message) use ($email) {
                    $message->to($email->value)
                        ->subject('[PharmaControl] Contraseña expirada');
                }
            );
        } catch (\Throwable $e) {
            Log::error('Fallo al enviar aviso de expiración', [
                'email' => $email->value,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
