<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Contract\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Yuan\AdminCore\Audit\LogsModuleActivity;

/**
 * @property int $id
 * @property string $name
 * @property string|null $alamat
 */
final class Customer extends Model
{
    use LogsModuleActivity, SoftDeletes;

    protected $table = 'contract_customers';

    protected $fillable = ['name', 'alamat'];
}
