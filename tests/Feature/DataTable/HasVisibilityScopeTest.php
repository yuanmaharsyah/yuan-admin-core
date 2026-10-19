<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Yuan\AdminCore\DataTable\HasVisibilityScope;
use Yuan\AdminCore\Tests\Fixtures\Modules\Demo\Models\DemoItem;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    DemoItem::query()->create(['name' => 'Milik Budi']);
    DemoItem::query()->create(['name' => 'Milik Andi']);
});

it('changes nothing by default', function (): void {
    expect(DemoItem::query()->visibleTo(makeUser())->count())->toBe(2)
        ->and(DemoItem::query()->visibleTo(null)->count())->toBe(2);
});

it('applies a model override to the list query', function (): void {
    $scoped = new class extends Model
    {
        use HasVisibilityScope;

        protected $table = 'demo_items';

        /**
         * @param  Builder<static>  $query
         */
        public function scopeVisibleTo(Builder $query, ?Authenticatable $user): void
        {
            $query->where('name', 'like', '%'.$user?->getAttribute('name'));
        }
    };

    expect($scoped->newQuery()->visibleTo(makeUser('Andi'))->pluck('name')->all())->toBe(['Milik Andi']);
});
