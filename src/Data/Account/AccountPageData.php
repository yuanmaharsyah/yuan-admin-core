<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Data\Account;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Props of `Core::Account/Settings`: Profil, Notifikasi and Keamanan tabs. */
#[TypeScript]
final class AccountPageData extends Data
{
    /**
     * @param  list<SessionData>|null  $sessions  Null when sessions aren't stored in the database.
     */
    public function __construct(
        public ProfileData $profile,
        #[LiteralTypeScriptType("'profil' | 'notifikasi' | 'keamanan'")]
        public string $tab,
        public string $password_submit_href,
        public string $password_hint,
        // Null while Fortify's two-step verification is off in the project.
        public ?TwoFactorData $two_factor,
        #[DataCollectionOf(SessionData::class)]
        public ?array $sessions,
        public string $sign_out_others_href,
    ) {}
}
