<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Business columns without LogsModuleActivity.
 */
final class Leaky extends Model
{
    protected $table = 'contract_customers';

    protected $fillable = ['name'];
}
