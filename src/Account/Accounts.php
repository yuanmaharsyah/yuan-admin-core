<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Account;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yuan\AdminCore\Attachments\Attachment;
use Yuan\AdminCore\Audit\Audit;
use Yuan\AdminCore\Audit\AuditEvent;
use Yuan\AdminCore\Data\Account\SessionData;
use Yuan\AdminCore\Modules\Users\Models\User;

/** The writes and queries behind Akun (ProfileController stays thin). */
final class Accounts
{
    /** Removes the profile photo (each file audited, one batch). */
    public function removeAvatar(User $user): void
    {
        Audit::batch(fn () => DB::transaction(function () use ($user): void {
            $user->attachments('avatar')->get()->each(function (Attachment $file) use ($user): void {
                Audit::record(AuditEvent::AttachmentDeleted, $user, ['name' => $file->original_name, 'slot' => $file->collection]);
                $file->delete();
            });
        }));
    }

    /**
     * @return list<SessionData>|null
     */
    public function sessions(Request $request, User $user): ?array
    {
        if (config('session.driver') !== 'database') {
            return null;
        }

        $current = $request->hasSession() ? $request->session()->getId() : null;

        return array_values(DB::table(config()->string('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->orderByDesc('last_activity')
            ->limit(10)
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $row): SessionData => new SessionData(
                agent: self::agent(is_string($row->user_agent ?? null) ? $row->user_agent : ''),
                ip: is_string($row->ip_address ?? null) ? $row->ip_address : null,
                last_active: Carbon::createFromTimestamp(is_numeric($row->last_activity ?? null) ? (int) $row->last_activity : 0)->toIso8601String(),
                current: ($row->id ?? null) === $current,
            ))
            ->all());
    }

    /** "Chrome · macOS"-style name of a user agent (no library: the common cases). */
    private static function agent(string $agent): string
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => __('Peramban'),
        };
        $system = match (true) {
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return $system === null ? $browser : "{$browser} · {$system}";
    }
}
