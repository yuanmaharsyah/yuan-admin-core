<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Security events (`log_name` = `auth` for sign-in events). The causer is set
 * explicitly because the guard has no user yet (login) or any more (logout).
 * A failed or locked login stores the typed identifier only, never the password.
 */
final class AuthAuditSubscriber
{
    /**
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            Login::class => 'login',
            Logout::class => 'logout',
            Failed::class => 'failed',
            Lockout::class => 'lockout',
            PasswordReset::class => 'passwordReset',
        ];
    }

    public function login(Login $event): void
    {
        $this->recordFor($event->user, AuditEvent::Login);
    }

    public function logout(Logout $event): void
    {
        $this->recordFor($event->user, AuditEvent::Logout);
    }

    public function passwordReset(PasswordReset $event): void
    {
        $this->recordFor($event->user, AuditEvent::PasswordReset);
    }

    /**
     * The matched user (if any) is the subject, so the SA visibility rule can
     * hide failed attempts on Super Admin accounts.
     */
    public function failed(Failed $event): void
    {
        Audit::record(
            AuditEvent::LoginFailed,
            $event->user instanceof Model ? $event->user : null,
            ['username' => $event->credentials[$this->usernameField()] ?? null],
        );
    }

    public function lockout(Lockout $event): void
    {
        Audit::record(AuditEvent::LoginLocked, meta: ['username' => $event->request->input($this->usernameField())]);
    }

    private function recordFor(?Authenticatable $user, AuditEvent $event): void
    {
        if ($user instanceof Model) {
            Audit::actingAs($user, fn () => Audit::record($event, $user));
        }
    }

    /**
     * The login identifier field; follows Fortify's setting when it is installed.
     */
    private function usernameField(): string
    {
        $field = config('fortify.username', 'email');

        return is_string($field) ? $field : 'email';
    }
}
