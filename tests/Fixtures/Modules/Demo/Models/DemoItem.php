<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Yuan\AdminCore\Audit\LogsModuleActivity;

final class DemoItem extends Model
{
    use LogsModuleActivity, SoftDeletes;

    protected $table = 'demo_items';

    protected $fillable = ['name', 'nik', 'no_hp', 'secret', 'pin'];

    protected $hidden = ['pin'];
}
