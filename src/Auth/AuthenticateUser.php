<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;

/**
 * The login check, for `Fortify::authenticateUsing()`: the login field takes a
 * username or an email (an `@` means email). Wrong credentials
 * return null (Fortify fires `Failed`, counts the attempt and shows its
 * message); the right password on an inactive account is refused with
 * `login_failed` (reason `inactive`).
 */
final class AuthenticateUser
{
    public function __invoke(Request $request): ?Authenticatable
    {
        $field = self::usernameField();
        $username = $request->input($field);
        $password = $request->input('password');
        $users = Auth::createUserProvider(config()->string('auth.guards.'.config()->string('auth.defaults.guard').'.provider'));

        if (! is_string($username) || $username === '' || ! is_string($password) || $users === null) {
            return null;
        }

        $user = $users->retrieveByCredentials(self::credentials($username));

        if ($user === null || ! $users->validateCredentials($user, ['password' => $password])) {
            return null;
        }

        if ($user instanceof Model && ! $user->getAttribute('is_active')) {
            Audit::record(AuditEvent::LoginFailed, $user, ['username' => $username, 'reason' => 'inactive']);

            throw ValidationException::withMessages([$field => __('Akun Anda tidak aktif. Hubungi administrator.')]);
        }

        return $user;
    }

    /**
     * The column a login value is looked up in: one field takes a username or
     * an email (ui-design §4.7); an `@` means email.
     *
     * @return array<string, string>
     */
    public static function credentials(string $login): array
    {
        return [str_contains($login, '@') ? 'email' : 'username' => $login];
    }

    /**
     * The login identifier field; follows Fortify's setting when it is installed.
     */
    public static function usernameField(): string
    {
        $field = config('fortify.username', 'email');

        return is_string($field) ? $field : 'email';
    }
}
