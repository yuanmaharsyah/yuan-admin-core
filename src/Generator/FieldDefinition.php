<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Generator;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * One `name:type` of `--fields`: a trailing `?` makes it nullable;
 * `enum(a,b)` lists the values, `foreign(table)` names the target table,
 * `file(single,required,pdf|jpg,2048)` sets the attachment slot.
 */
final readonly class FieldDefinition
{
    public const array RESERVED = ['id', 'lock_version', 'created_at', 'updated_at', 'deleted_at', 'files'];

    /**
     * @param  list<string>  $values  An enum's values.
     * @param  list<string>  $mimes  A file slot's extensions (empty: the configured default).
     */
    public function __construct(
        public string $name,
        public FieldType $type,
        public bool $nullable = false,
        public array $values = [],
        public ?string $table = null,
        public bool $multiple = true,
        public bool $required = false,
        public array $mimes = [],
        public ?int $maxKb = null,
    ) {}

    public static function parse(string $spec): self
    {
        $spec = trim($spec);

        if (preg_match('/^([a-z][a-z0-9_]*):([a-z]+)(?:\(([^)]*)\))?(\?)?$/', $spec, $parts) !== 1) {
            throw new InvalidArgumentException("Field [{$spec}]: write it as name:type, in snake_case (e.g. issued_on:date, notes:text?).");
        }

        [, $name, $typeName] = $parts;
        $arguments = ($parts[3] ?? '') === '' ? [] : array_map(trim(...), explode(',', $parts[3]));
        $nullable = ($parts[4] ?? '') === '?';
        $type = FieldType::tryFrom($typeName)
            ?? throw new InvalidArgumentException("Field [{$name}]: unknown type [{$typeName}]; use ".implode(', ', array_column(FieldType::cases(), 'value')).'.');

        if (in_array($name, self::RESERVED, true)) {
            throw new InvalidArgumentException("Field [{$name}]: the name is reserved (".implode(', ', self::RESERVED).').');
        }

        return match ($type) {
            FieldType::Enum => self::enum($name, $arguments, $nullable),
            FieldType::Foreign => self::foreign($name, $arguments, $nullable),
            FieldType::File => self::file($name, $arguments),
            default => $arguments === [] ? new self($name, $type, $nullable) : throw new InvalidArgumentException("Field [{$name}]: {$typeName} takes no arguments."),
        };
    }

    /** The label in the UI and messages: `tanggal_mulai` → "Tanggal mulai", `customer_id` → "Customer". */
    public function label(): string
    {
        return Str::ucfirst(Str::lower(Str::headline($this->type === FieldType::Foreign ? Str::beforeLast($this->name, '_id') : $this->name)));
    }

    /** The belongsTo relation of a foreign field: `customer_id` → `customer`. */
    public function relation(): string
    {
        return Str::camel(Str::beforeLast($this->name, '_id'));
    }

    /** The relation's display column in the Data objects: `customer_id` → `customer_name`. */
    public function relationLabel(): string
    {
        return Str::beforeLast($this->name, '_id').'_name';
    }

    /** The PHP enum of an enum field: `Invoice` + `status` → `InvoiceStatus`. */
    public function enumClass(string $module): string
    {
        return $module.Str::studly($this->name);
    }

    /** The PHP enum case of a value: `in_progress` → `InProgress`. */
    public static function enumCase(string $value): string
    {
        return Str::studly($value);
    }

    /**
     * @param  list<string>  $arguments
     */
    private static function enum(string $name, array $arguments, bool $nullable): self
    {
        if ($arguments === [] || array_filter($arguments, fn (string $value): bool => preg_match('/^[a-z][a-z0-9_]*$/', $value) !== 1) !== []) {
            throw new InvalidArgumentException("Field [{$name}]: enum(...) needs snake_case values, e.g. enum(draft,paid).");
        }

        if (count(array_unique($arguments)) !== count($arguments)) {
            throw new InvalidArgumentException("Field [{$name}]: enum values must be unique.");
        }

        return new self($name, FieldType::Enum, $nullable, values: $arguments);
    }

    /**
     * @param  list<string>  $arguments
     */
    private static function foreign(string $name, array $arguments, bool $nullable): self
    {
        if (count($arguments) !== 1 || preg_match('/^[a-z][a-z0-9_]*$/', $arguments[0]) !== 1 || ! str_ends_with($name, '_id')) {
            throw new InvalidArgumentException("Field [{$name}]: write a relation as {name}_id:foreign(table), e.g. customer_id:foreign(customers).");
        }

        return new self($name, FieldType::Foreign, $nullable, table: $arguments[0]);
    }

    /**
     * @param  list<string>  $arguments
     */
    private static function file(string $name, array $arguments): self
    {
        $multiple = true;
        $required = false;
        $mimes = [];
        $maxKb = null;

        foreach ($arguments as $argument) {
            match (true) {
                $argument === 'single' => $multiple = false,
                $argument === 'multiple' => $multiple = true,
                $argument === 'required' => $required = true,
                ctype_digit($argument) => $maxKb = (int) $argument,
                preg_match('/^[a-z0-9]+(\|[a-z0-9]+)*$/', $argument) === 1 => $mimes = explode('|', $argument),
                default => throw new InvalidArgumentException("Field [{$name}]: unknown file option [{$argument}]; use single or multiple, required, extensions like pdf|jpg, and a size in KB."),
            };
        }

        return new self($name, FieldType::File, false, multiple: $multiple, required: $required, mimes: $mimes, maxKb: $maxKb);
    }
}
