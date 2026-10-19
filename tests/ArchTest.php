<?php

declare(strict_types=1);

arch('package code declares strict types', function (): void {
    expect('Yuan\AdminCore')->toUseStrictTypes();
});

arch('no debugging helpers', function (): void {
    expect(['dd', 'dump', 'ray', 'var_dump'])->not->toBeUsed();
});

arch('no env() in package code', function (): void {
    expect('env')->not->toBeUsed();
});
