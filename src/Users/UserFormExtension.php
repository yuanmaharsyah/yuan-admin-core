<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Users;

use Illuminate\Database\Eloquent\Model;
use Yuan\AdminCore\Data\Users\ExtraFieldData;

/**
 * Extra user fields for a project (spec §2.2), set in
 * `admin-core.users.form_extension`. Each field is a column the project adds
 * to `users` with its own migration (NIP, jabatan, unit_id…); the core Users
 * forms show it, validate it with rules(), save it and list it on the detail
 * page. A select's options can come from the database (units for data scope).
 */
interface UserFormExtension
{
    /**
     * @return list<ExtraFieldData>
     */
    public function fields(): array;

    /**
     * Validation per field name, for a new user (null) or the edited one.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(?Model $user): array;
}
