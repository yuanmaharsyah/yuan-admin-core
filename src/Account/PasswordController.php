<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Account;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Auth\PasswordRules;
use Yuan\AdminCore\Data\Auth\ChangePasswordPageData;
use Yuan\AdminCore\Data\Auth\UpdatePasswordData;
use Yuan\AdminCore\Data\FlashData;
use Yuan\AdminCore\Modules\Users\Models\User;

/**
 * The signed-in user's own password change (ui-design §4.7). Saving raises the
 * session version: other sessions end, this one carries on. After an admin
 * created the account or reset its password, the page is mandatory
 * (RequirePasswordChange) and its only way out is Keluar.
 */
final class PasswordController
{
    public function edit(Request $request): Response
    {
        $user = self::user($request);

        return Inertia::render('Core::Auth/ChangePassword', new ChangePasswordPageData(
            submit_href: route('account.password.update'),
            back_href: route(config()->string('admin-core.routes.home')),
            logout_href: route('logout'),
            required: $user->must_change_password,
            user_name: $user->name,
            level_label: $user->level()?->label,
            password_hint: PasswordRules::hint(),
        ));
    }

    public function update(Request $request, UpdatePasswordData $data): RedirectResponse
    {
        $user = self::user($request);

        DB::transaction(function () use ($user, $data): void {
            $user->forceFill(['password' => $data->password, 'must_change_password' => false])->save();
            Audit::record(AuditEvent::PasswordChanged, $user, module: 'users');
        });
        Inertia::flash((new FlashData(success: __('Kata sandi berhasil diubah.')))->toArray());

        return to_route(config()->string('admin-core.routes.home'));
    }

    private static function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
