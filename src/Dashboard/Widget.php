<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Dashboard;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use LogicException;
use Spatie\LaravelData\Data;
use Yuan\AdminCore\Data\Widgets\WidgetMetaData;

/**
 * A Beranda card (spec §7.2), listed in its module's `module.php` → `widgets`.
 * `data()` must start from `->visibleTo($user)` and return the type's Data
 * object; it should resolve in under 300 ms on realistic data.
 */
abstract class Widget
{
    /** `{slug}.{name}`, globally unique. */
    abstract public function key(): string;

    abstract public function label(): string;

    abstract public function type(): WidgetType;

    abstract public function data(Authenticatable $user, DashboardRange $range): Data;

    public function size(): WidgetSize
    {
        return WidgetSize::Sm;
    }

    /** Spans two rows on wide screens. */
    public function tall(): bool
    {
        return false;
    }

    public function order(): int
    {
        return 100;
    }

    /** Without it the widget is left out of the page entirely. */
    public function permission(): string
    {
        return $this->slug().'.view';
    }

    public function usesRange(): bool
    {
        return true;
    }

    /** 0: no cache. */
    public function cacheSeconds(): int
    {
        return 300;
    }

    /** true: one cache entry per level, only when the data doesn't depend on the user. */
    public function sharedCache(): bool
    {
        return false;
    }

    /** "Lihat semua ›": the module's list. */
    public function href(): ?string
    {
        return Route::has($this->slug().'.index') ? route($this->slug().'.index') : null;
    }

    public function emptyText(): string
    {
        return __('Belum ada data.');
    }

    /** The module slug, from the key. */
    final public function slug(): string
    {
        return Str::before($this->key(), '.');
    }

    /** The Inertia prop name: no dots or dashes, so partial reloads can name it. */
    final public function prop(): string
    {
        return 'widget_'.str_replace(['.', '-'], '_', $this->key());
    }

    final public function meta(): WidgetMetaData
    {
        return new WidgetMetaData(
            key: $this->key(),
            prop: $this->prop(),
            label: __($this->label()),
            type: $this->type()->value,
            size: $this->size()->value,
            tall: $this->tall(),
            uses_range: $this->usesRange(),
            href: $this->href(),
            empty: $this->emptyText(),
        );
    }

    /** `data()` through the cache, checked against the type. */
    final public function resolve(Authenticatable $user, DashboardRange $range): Data
    {
        $compute = function () use ($user, $range): Data {
            $data = $this->data($user, $range);
            $class = $this->type()->dataClass();

            if (! $data instanceof $class) {
                throw new LogicException(static::class.'::data() must return '.$class.' for a '.$this->type()->value.' widget.');
            }

            return $data;
        };

        if ($this->cacheSeconds() <= 0) {
            return $compute();
        }

        $owner = $this->sharedCache() && method_exists($user, 'level') ? 'level:'.($user->level()?->getKey() ?? 'none') : 'user:'.$user->getAuthIdentifier();

        return Cache::remember(implode(':', ['admin-core:widget', $this->key(), $this->usesRange() ? $range->key : '-', $owner]), $this->cacheSeconds(), $compute);
    }
}
