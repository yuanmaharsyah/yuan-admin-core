<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\Fixtures\Contract\Invoice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Yuan\AdminCore\Audit\LogsModuleActivity;
use Yuan\AdminCore\DataTable\HasVisibilityScope;
use Yuan\AdminCore\Tests\Fixtures\Contract\Customer\Models\Customer;

/**
 * @property int $id
 * @property string $number
 * @property int $customer_id
 * @property string|null $npwp
 * @property int $total
 * @property-read Customer $customer
 */
final class Invoice extends Model
{
    use HasVisibilityScope, LogsModuleActivity, SoftDeletes;

    protected $table = 'contract_invoices';

    protected $fillable = ['number', 'customer_id', 'npwp', 'total'];

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function auditLabel(): string
    {
        return $this->number;
    }

    protected function casts(): array
    {
        return ['customer_id' => 'integer', 'total' => 'integer'];
    }
}
