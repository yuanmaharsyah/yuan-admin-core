<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Yuan\AdminCore\Generator\FieldDefinition;
use Yuan\AdminCore\Generator\ModuleBlueprint;
use Yuan\AdminCore\Generator\ModuleWriter;

// make:module and make:widget (Phase 08b): option parsing and checks before
// anything is written, every stub for every action subset, and the commands'
// write / refuse / --force behaviour. The golden module in the skeleton
// proves the output passes every gate.

const EVERY_FIELD = 'nama:string,kode:string?,catatan:text?,jumlah:integer,berat:decimal?,harga:currency,aktif:boolean,mulai:date,selesai:datetime?,status:enum(draf,aktif),berkas:file(single,required,pdf|jpg,2048),lampiran:file';

/**
 * @param  array<mixed>  $actions
 */
function blueprint(string $fields = EVERY_FIELD, array $actions = ['view', 'create', 'edit', 'delete', 'download'], bool $superadminOnly = false): ModuleBlueprint
{
    return ModuleBlueprint::make('Barang', $fields, array_values(array_filter($actions, is_string(...))), 'master', 'box', superadminOnly: $superadminOnly);
}

it('parses every field type, nullable fields and their arguments', function (): void {
    $fields = collect(blueprint()->fields)->keyBy('name');
    $field = fn (string $name): FieldDefinition => $fields->get($name) ?? throw new LogicException("No field {$name}.");

    expect($fields->map(fn (FieldDefinition $field): string => $field->type->value)->all())->toBe([
        'nama' => 'string', 'kode' => 'string', 'catatan' => 'text', 'jumlah' => 'integer', 'berat' => 'decimal', 'harga' => 'currency',
        'aktif' => 'boolean', 'mulai' => 'date', 'selesai' => 'datetime', 'status' => 'enum', 'berkas' => 'file', 'lampiran' => 'file',
    ])
        ->and($field('kode')->nullable)->toBeTrue()
        ->and($field('nama')->nullable)->toBeFalse()
        ->and($field('status')->values)->toBe(['draf', 'aktif'])
        ->and([$field('berkas')->multiple, $field('berkas')->required, $field('berkas')->mimes, $field('berkas')->maxKb])->toBe([false, true, ['pdf', 'jpg'], 2048])
        ->and([$field('lampiran')->multiple, $field('lampiran')->required, $field('lampiran')->mimes, $field('lampiran')->maxKb])->toBe([true, false, [], null])
        ->and(FieldDefinition::parse('customer_id:foreign(customers)')->table)->toBe('customers')
        ->and(FieldDefinition::parse('customer_id:foreign(customers)')->label())->toBe('Customer')
        ->and(FieldDefinition::parse('tanggal_mulai:date')->label())->toBe('Tanggal mulai');
});

it('names the module from its class name', function (): void {
    $module = ModuleBlueprint::make('PurchaseOrder', 'nomor:string', ['view'], 'master', 'box');

    expect([$module->slug, $module->table(), $module->variable(), $module->label, $module->labelField()?->name])
        ->toBe(['purchase-orders', 'purchase_orders', 'purchaseOrder', 'PurchaseOrder', 'nomor']);
});

it('refuses bad input before writing anything', function (string $fields, array $overrides, string $message): void {
    $make = fn () => ModuleBlueprint::make(
        $overrides['name'] ?? 'Barang',
        $fields,
        $overrides['actions'] ?? ['view'],
        $overrides['group'] ?? 'master',
        $overrides['icon'] ?? 'box',
    );

    expect($make)->toThrow(InvalidArgumentException::class, $message);
})->with([
    'unknown type' => ['nama:teks', [], 'unknown type [teks]'],
    'camelCase name' => ['namaBarang:string', [], 'snake_case'],
    'reserved name' => ['lock_version:integer', [], 'reserved'],
    'enum without values' => ['status:enum()', [], 'enum(...) needs snake_case values'],
    'repeated enum value' => ['status:enum(a,a)', [], 'enum values must be unique'],
    'foreign without _id' => ['customer:foreign(customers)', [], '{name}_id:foreign(table)'],
    'foreign to an unknown table' => ['gudang_id:foreign(gudangs)', [], 'no module\'s model uses the table [gudangs]'],
    'unknown file option' => ['scan:file(pdf/jpg)', [], 'unknown file option [pdf/jpg]'],
    'arguments on a plain type' => ['nama:string(30)', [], 'takes no arguments'],
    'repeated field' => ['nama:string,nama:text', [], 'Field names must be unique'],
    'no fields' => ['', [], 'at least one field'],
    'singular PascalCase name' => ['nama:string', ['name' => 'barang'], 'PascalCase'],
    'custom action' => ['nama:string', ['actions' => ['view', 'approve']], 'Custom actions (approve)'],
    'unknown group' => ['nama:string', ['group' => 'gudang'], 'must be one of the configured menu groups'],
    'unknown icon' => ['nama:string', ['icon' => 'box-unicorn'], 'must be a lucide icon name'],
    'taken slug' => ['nama:string', ['name' => 'Demo'], 'already exists'],
]);

it('lists masked fields for the audit assertions and warns about personal-looking ones', function (): void {
    $module = ModuleBlueprint::make('Pegawai', 'nama:string,npwp:string?,nip:string,email_kantor:string?', ['view'], 'master', 'box');

    expect($module->maskedFields())->toBe(['npwp'])
        ->and($module->unmaskedPersonalFields())->toBe(['nip', 'email_kantor']);
});

it('renders valid PHP and no leftover placeholder for every action subset', function (array $actions, bool $superadminOnly): void {
    $files = (new ModuleWriter(blueprint(actions: $actions, superadminOnly: $superadminOnly), '2026_10_24_000000'))->files();
    $folder = temporaryRoot();

    foreach ($files as $path => $contents) {
        expect($contents)->not->toMatch('/\{\{ [A-Za-z_]+ \}\}/');

        if (str_ends_with($path, '.php')) {
            file_put_contents("{$folder}/check.php", $contents);
            exec('php -l '.escapeshellarg("{$folder}/check.php").' 2>&1', $output, $status);
            expect($status)->toBe(0, "{$path}: ".implode("\n", $output));
        }
    }

    expect(array_key_exists('Data/StoreBarangData.php', $files))->toBe(in_array('create', $actions, true))
        ->and(array_key_exists('resources/js/pages/Edit.tsx', $files))->toBe(in_array('edit', $actions, true))
        ->and(array_key_exists('Exports/BarangExport.php', $files))->toBe(in_array('download', $actions, true))
        ->and(array_key_exists('resources/js/components/BarangForm.tsx', $files))->toBe(array_intersect(['create', 'edit'], $actions) !== [])
        ->and(str_contains($files['Routes/web.php'], 'permission:'))->toBe(! $superadminOnly);
})->with([
    'view only' => [['view'], false],
    'view, create' => [['view', 'create'], false],
    'view, edit' => [['view', 'edit'], false],
    'view, delete' => [['view', 'delete'], false],
    'view, download' => [['view', 'download'], false],
    'every action' => [['view', 'create', 'edit', 'delete', 'download'], false],
    'superadmin_only' => [['view', 'create', 'edit', 'delete'], true],
]);

it('writes a module folder, refuses an existing one, and --force replaces only its own', function (): void {
    $root = temporaryRoot();
    $options = ['name' => 'Barang', '--fields' => 'nama:string,status:enum(draf,aktif)', '--group' => 'master', '--icon' => 'box', '--path' => $root, '--no-post' => true, '--migration-date' => '2026_10_24_000000'];

    expect(Artisan::call('make:module', $options))->toBe(0)
        ->and("{$root}/Barang/Database/Migrations/2026_10_24_000000_create_barangs_table.php")->toBeFile()
        ->and(Artisan::call('make:module', $options))->toBe(1)
        ->and(Artisan::output())->toContain('already exists')
        ->and(Artisan::call('make:module', [...$options, '--force' => true]))->toBe(0);

    file_put_contents("{$root}/Barang/module.php", "<?php\n\nreturn [];\n");

    expect(Artisan::call('make:module', [...$options, '--force' => true]))->toBe(1)
        ->and(Artisan::output())->toContain("wasn't written by make:module");
});

it('adds a widget with its test and lists it in module.php', function (): void {
    $root = temporaryRoot();
    Artisan::call('make:module', ['name' => 'Barang', '--fields' => 'nama:string', '--group' => 'master', '--icon' => 'box', '--path' => $root, '--no-post' => true]);

    expect(Artisan::call('make:widget', ['module' => 'Barang', 'name' => 'Total', '--type' => 'stat', '--path' => $root]))->toBe(0)
        ->and(file_get_contents("{$root}/Barang/Widgets/Total.php"))->toContain("return 'barangs.total';")
        ->and("{$root}/Barang/Tests/Feature/TotalWidgetTest.php")->toBeFile()
        ->and(file_get_contents("{$root}/Barang/module.php"))->toContain('use Modules\Barang\Widgets\Total;', 'Total::class')
        ->and(Artisan::call('make:widget', ['module' => 'Barang', 'name' => 'Total', '--path' => $root]))->toBe(1)
        ->and(Artisan::call('make:widget', ['module' => 'Barang', 'name' => 'PerStatus', '--type' => 'segment', '--path' => $root]))->toBe(1)
        ->and(Artisan::output())->toContain('counts by an enum column');
});

it('refuses a widget for a superadmin_only module', function (): void {
    $root = temporaryRoot();
    Artisan::call('make:module', ['name' => 'Barang', '--fields' => 'nama:string', '--group' => 'master', '--icon' => 'box', '--superadmin-only' => true, '--path' => $root, '--no-post' => true]);

    expect(Artisan::call('make:widget', ['module' => 'Barang', 'name' => 'Total', '--path' => $root]))->toBe(1)
        ->and(Artisan::output())->toContain('superadmin_only');
});
