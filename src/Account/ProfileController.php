<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Account;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Yuan\AdminCore\Data\Account\ProfileData;
use Yuan\AdminCore\Data\Account\UpdateProfileData;
use Yuan\AdminCore\Data\BreadcrumbData;
use Yuan\AdminCore\Data\BreadcrumbsData;
use Yuan\AdminCore\Data\FlashData;
use Yuan\AdminCore\Modules\Users\Models\User;

/**
 * The signed-in user's own name and email (derived screen). The username and
 * the level are shown, changed only by an admin.
 */
final class ProfileController
{
    public function edit(Request $request): Response
    {
        $user = self::user($request);

        return Inertia::render('Core::Account/Profile', [
            'profile' => new ProfileData(
                name: $user->name,
                email: $user->email,
                username: $user->username,
                level_label: $user->level()?->label,
                lock_version: $user->lock_version,
                submit_href: route('account.profile.update'),
                password_href: route('account.password.edit'),
            ),
            'breadcrumbs' => BreadcrumbsData::of(new BreadcrumbData(label: __('Profil'), href: null)),
        ]);
    }

    public function update(Request $request, UpdateProfileData $data): RedirectResponse
    {
        $user = self::user($request);

        $user->updateUnlessStale(['name' => $data->name, 'email' => Str::lower($data->email)], $data->lock_version);
        Inertia::flash((new FlashData(success: __('Profil disimpan.')))->toArray());

        return to_route('account.profile.edit');
    }

    private static function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
