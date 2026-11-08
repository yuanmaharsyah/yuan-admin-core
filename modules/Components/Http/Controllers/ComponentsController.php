<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Modules\Components\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Yuan\AdminCore\Data\DataTableStateData;
use Yuan\AdminCore\Data\PaginatedData;
use Yuan\AdminCore\Modules\Components\Data\ComponentsPageData;
use Yuan\AdminCore\Modules\Components\Data\SampleRowData;

/**
 * Komponen (DEVELOPER.md R21), Super Admin only: every house-style component,
 * live, with its import and usage. The examples on the page are documentation
 * fixtures; only the DataTable needs a server-side page, built here.
 */
final class ComponentsController
{
    public function index(Request $request): Response
    {
        Gate::authorize('admin-core.superadmin');

        // Documentation fixture for the DataTable example.
        $rows = [
            new SampleRowData(1, 'Toko Sinar Jaya', 'Bandung', 'paid', 12_500_000, '2026-11-07'),
            new SampleRowData(2, 'CV Maju Bersama', 'Surabaya', 'sent', 4_250_000, '2026-11-06'),
            new SampleRowData(3, 'PT Nusa Abadi', 'Jakarta', 'draft', 870_000, '2026-11-06'),
            new SampleRowData(4, 'Warung Bu Sri', 'Yogyakarta', 'paid', 1_150_000, '2026-11-04'),
            new SampleRowData(5, 'UD Sumber Rezeki', 'Medan', 'sent', 6_300_000, '2026-11-02'),
            new SampleRowData(6, 'Koperasi Harapan', 'Makassar', 'paid', 2_480_000, '2026-11-01'),
        ];

        return Inertia::render('Components::Index', new ComponentsPageData(
            table: PaginatedData::fromPaginator(new LengthAwarePaginator($rows, count($rows), 25, 1, ['path' => $request->url()]), SampleRowData::class),
            state: new DataTableStateData(search: null, sort: null, filter: [], per_page: 25, per_page_options: [25], sortable: [], filters: [], searchable: false),
        ));
    }
}
