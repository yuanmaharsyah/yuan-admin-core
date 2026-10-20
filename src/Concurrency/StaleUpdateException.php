<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Concurrency;

use Symfony\Component\HttpKernel\Exception\HttpException;

/** A save of data someone else changed since the form loaded: HTTP 409, which opens the StaleUpdateDialog. */
final class StaleUpdateException extends HttpException
{
    public function __construct()
    {
        parent::__construct(409, __('Data ini telah diubah oleh pengguna lain.'));
    }
}
