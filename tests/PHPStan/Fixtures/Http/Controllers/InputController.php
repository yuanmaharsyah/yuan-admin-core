<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Tests\PHPStan\Fixtures\Http\Controllers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;

final class InputController
{
    /**
     * @return list<mixed>
     */
    public function allowed(Request $request): array
    {
        return [$request->input('name'), $request->only(['name']), $request->query('page'), $request->user()];
    }

    /**
     * @return list<mixed>
     */
    public function raw(Request $request, FormRequest $form): array
    {
        return [
            $request->all(),
            $request->input(),
            $form->validated(),
            RequestFacade::all(),
        ];
    }
}
