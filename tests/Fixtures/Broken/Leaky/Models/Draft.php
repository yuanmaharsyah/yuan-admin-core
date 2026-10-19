<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Broken\Leaky\Models;

use Illuminate\Database\Eloquent\Model;
use Yuan\AdminCore\Audit\NotAudited;

#[NotAudited(reason: ' ')]
final class Draft extends Model
{
    protected $table = 'contract_customers';

    protected $fillable = ['name'];
}
