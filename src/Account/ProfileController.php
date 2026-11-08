<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Account;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yuan\AdminCore\Attachments\Attachment;
use Yuan\AdminCore\Attachments\AttachmentUploads;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Auth\PasswordRules;
use Yuan\AdminCore\Data\Account\AccountPageData;
use Yuan\AdminCore\Data\Account\ProfileData;
use Yuan\AdminCore\Data\Account\SignOutSessionsData;
use Yuan\AdminCore\Data\Account\TwoFactorData;
use Yuan\AdminCore\Data\Account\UpdateAvatarData;
use Yuan\AdminCore\Data\Account\UpdateProfileData;
use Yuan\AdminCore\Data\BreadcrumbData;
use Yuan\AdminCore\Data\BreadcrumbsData;
use Yuan\AdminCore\Data\FlashData;
use Yuan\AdminCore\Modules\Users\Models\User;

/**
 * Akun (restyle 03): the signed-in user's own page with tabs. Profil: photo,
 * name, email, password. Notifikasi: reserved room. Keamanan: two-step
 * verification (Fortify's routes, when the project turned it on) and the
 * signed-in browsers. The username and the level are shown, changed only by
 * an admin.
 */
final class ProfileController
{
    public function __construct(private readonly Accounts $accounts) {}

    private const array TABS = ['profil', 'notifikasi', 'keamanan'];

    public function edit(Request $request): Response
    {
        $user = self::user($request);
        $tab = $request->query('tab');

        return Inertia::render('Core::Account/Settings', [
            'account' => new AccountPageData(
                profile: new ProfileData(
                    name: $user->name,
                    email: $user->email,
                    username: $user->username,
                    level_label: $user->level()?->label,
                    lock_version: $user->lock_version,
                    submit_href: route('account.profile.update'),
                    password_href: route('account.password.edit'),
                    avatar_url: self::avatarUrl($user),
                    avatar_submit_href: route('account.avatar.update'),
                    avatar_destroy_href: $user->attachments('avatar')->exists() ? route('account.avatar.destroy') : null,
                ),
                tab: is_string($tab) && in_array($tab, self::TABS, true) ? $tab : 'profil',
                password_submit_href: route('account.password.update'),
                password_hint: PasswordRules::hint(),
                two_factor: self::twoFactor($user),
                sessions: $this->accounts->sessions($request, $user),
                sign_out_others_href: route('account.sessions.destroy'),
            ),
            'breadcrumbs' => BreadcrumbsData::of(new BreadcrumbData(label: __('Akun'), href: null)),
        ]);
    }

    public function update(Request $request, UpdateProfileData $data): RedirectResponse
    {
        $user = self::user($request);

        $user->updateUnlessStale(['name' => $data->name, 'email' => Str::lower($data->email)], $data->lock_version);
        Inertia::flash((new FlashData(success: __('Profil disimpan.')))->toArray());

        return to_route('account.profile.edit');
    }

    /** A new photo replaces the old one (a single attachment slot, audited). */
    public function updateAvatar(Request $request, UpdateAvatarData $data): RedirectResponse
    {
        $user = self::user($request);

        AttachmentUploads::saveWith(fn (): User => $user, ['avatar' => $data->avatar], $user);
        Inertia::flash((new FlashData(success: __('Foto profil disimpan.')))->toArray());

        return to_route('account.profile.edit');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = self::user($request);

        $this->accounts->removeAvatar($user);
        Inertia::flash((new FlashData(success: __('Foto profil dihapus.')))->toArray());

        return to_route('account.profile.edit');
    }

    /**
     * The photo itself, behind a signed URL from the shared props. Shown on
     * every page (sidebar, user menu), so it isn't an audited download: the
     * upload and the removal are.
     */
    public function showAvatar(int $user): StreamedResponse
    {
        $file = Attachment::query()
            ->where('attachable_type', (new (self::userClass()))->getMorphClass())
            ->where('attachable_id', $user)
            ->where('collection', 'avatar')
            ->latest('id')
            ->firstOrFail();

        return Storage::disk($file->disk)->response($file->path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'Content-Type' => $file->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function signOutOthers(Request $request, SignOutSessionsData $data): RedirectResponse
    {
        $user = self::user($request);

        $user->signOutOtherSessions();
        Audit::record(AuditEvent::Logout, $user, ['scope' => 'other_sessions']);
        Inertia::flash((new FlashData(success: __('Perangkat lain sudah dikeluarkan.')))->toArray());

        return to_route('account.profile.edit', ['tab' => 'keamanan']);
    }

    /**
     * A signed URL of the photo, stable for the hour (so the browser cache
     * holds), or null without one.
     */
    public static function avatarUrl(User $user): ?string
    {
        $file = $user->attachments('avatar')->first(['id']);

        if ($file === null || ! Route::has('account.avatar.show')) {
            return null;
        }

        return URL::temporarySignedRoute('account.avatar.show', Carbon::now()->startOfHour()->addHours(2), ['user' => $user->getKey(), 'v' => $file->getKey()]);
    }

    private static function twoFactor(User $user): ?TwoFactorData
    {
        if (! Route::has('two-factor.enable')) {
            return null;
        }

        $state = match (true) {
            $user->getAttribute('two_factor_confirmed_at') !== null => 'on',
            $user->getAttribute('two_factor_secret') !== null => 'pending',
            default => 'off',
        };

        return new TwoFactorData(
            state: $state,
            required: settings('keamanan.wajib_2fa') === true,
            enable_href: route('two-factor.enable'),
            confirm_href: route('two-factor.confirm'),
            disable_href: route('two-factor.disable'),
            qr_href: route('two-factor.qr-code'),
            secret_href: route('two-factor.secret-key'),
            recovery_href: route('two-factor.recovery-codes'),
            regenerate_href: route('two-factor.regenerate-recovery-codes'),
            confirm_status_href: route('password.confirmation'),
            confirm_password_href: route('password.confirm.store'),
        );
    }

    /**
     * @return class-string<User>
     */
    private static function userClass(): string
    {
        $class = config()->string('admin-core.models.user');

        return is_a($class, User::class, true) ? $class : User::class;
    }

    private static function user(Request $request): User
    {
        $user = $request->user() ?? Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
