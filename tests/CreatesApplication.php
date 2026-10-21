<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * Environment 'testing' di-set secara eksplisit sebelum app diboot,
     * agar phpunit.xml dapat diterapkan dan middleware CSRF dilewati
     * saat pengujian (POST request).
     */
    public function createApplication(): Application
    {
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';
        putenv('APP_ENV=testing');

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
