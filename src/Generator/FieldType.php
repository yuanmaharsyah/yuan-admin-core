<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Generator;

/**
 * The `--fields` types of make:module (Phase 08 plan, "Field types →
 * generated code"). FieldDefinition turns a field of each type into its
 * code fragments.
 */
enum FieldType: string
{
    case String = 'string';
    case Text = 'text';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Currency = 'currency';
    case Boolean = 'boolean';
    case Date = 'date';
    case DateTime = 'datetime';
    case Enum = 'enum';
    case Foreign = 'foreign';
    case File = 'file';

    /** A column on the module's table (files are attachment slots instead). */
    public function hasColumn(): bool
    {
        return $this !== self::File;
    }

    /** Shown as a list column. */
    public function listed(): bool
    {
        return ! in_array($this, [self::Text, self::Boolean, self::File], true);
    }

    /** Sortable in the list (and so indexed). */
    public function sortable(): bool
    {
        return in_array($this, [self::String, self::Integer, self::Decimal, self::Currency, self::Date, self::DateTime], true);
    }

    /** Searched by the list's search box. */
    public function searchable(): bool
    {
        return $this === self::String;
    }
}
