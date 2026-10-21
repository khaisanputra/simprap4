<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function assertSessionHas(string $key, mixed $value = null): void
    {
        $this->assertTrue(session()->has($key), "Session does not contain key [{$key}].");

        if (! is_null($value)) {
            $this->assertSame($value, session()->get($key));
        }
    }
}
