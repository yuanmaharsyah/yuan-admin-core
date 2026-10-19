<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Contract\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Yuan\AdminCore\Audit\NotAudited;

#[NotAudited(reason: 'Fixed lookup rows written by the migration; never edited in the app.')]
final class CustomerType extends Model
{
    protected $table = 'contract_customer_types';

    protected $fillable = ['name'];
}
