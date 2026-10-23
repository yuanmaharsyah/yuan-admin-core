<?php

declare(strict_types=1);

use PHPStan\Testing\PHPStanTestCase;

require __DIR__.'/../vendor/autoload.php';

// The PHPStan rule tests (tests/PHPStan) compile PHPStan's container into the
// shared sys_get_temp_dir()/phpstan-tests. On a cold cache PHPStan reads an
// optional cache file with @file_get_contents; PHPUnit drops that suppressed
// warning, but Pest's printer still shows it as WARN on the first rule test.
// Building the container here, before PHPUnit's error handler is on, keeps the
// cold-cache work out of every test.
PHPStanTestCase::getContainer();
