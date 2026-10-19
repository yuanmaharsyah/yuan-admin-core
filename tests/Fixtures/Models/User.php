<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Yuan\AdminCore\Permissions\HasLevel;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 */
final class User extends Authenticatable
{
    use HasLevel, SoftDeletes;

    protected $fillable = ['name', 'email', 'username', 'password', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean', 'last_login_at' => 'datetime', 'sessions_invalidated_at' => 'datetime'];
    }
}
