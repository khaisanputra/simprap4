<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * Determine if the application is running unit tests.
     *
     * Diabaikan saat environment 'testing' agar seluruh pengujian
     * (POST request) dapat berjalan tanpa verifikasi CSRF.
     */
    protected function runningUnitTests()
    {
        return parent::runningUnitTests() || app()->environment('testing');
    }
}
