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
use Illuminate\Support\Facades\Auth;
use Yuan\AdminCore\Auth\AuthenticateUser;

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
     * The account the identifier belongs to (if any) is the subject, so the SA
     * visibility rule can hide attempts on Super Admin accounts.
     */
    public function failed(Failed $event): void
    {
        $username = $event->credentials[AuthenticateUser::usernameField()] ?? null;

        Audit::record(AuditEvent::LoginFailed, $event->user instanceof Model ? $event->user : $this->userFor($username), ['username' => $username]);
    }

    public function lockout(Lockout $event): void
    {
        $username = $event->request->input(AuthenticateUser::usernameField());

        Audit::record(AuditEvent::LoginLocked, $this->userFor($username), ['username' => $username]);
    }

    private function recordFor(?Authenticatable $user, AuditEvent $event): void
    {
        if ($user instanceof Model) {
            Audit::actingAs($user, fn () => Audit::record($event, $user));
        }
    }

    private function userFor(mixed $username): ?Model
    {
        if (! is_string($username) || $username === '') {
            return null;
        }

        $user = Auth::createUserProvider(config()->string('auth.guards.'.config()->string('auth.defaults.guard').'.provider'))
            ?->retrieveByCredentials([AuthenticateUser::usernameField() => $username]);

        return $user instanceof Model ? $user : null;
    }
}
