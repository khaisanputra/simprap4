<?php

namespace Tests\Feature;

use Tests\TestCase;

class DiagnosticEnvTest extends TestCase
{
    public function test_environment_diagnostic(): void
    {
        fwrite(STDERR, "ENV_DIAG start\n");
        fwrite(STDERR, 'APP_ENV='.app()->environment()."\n");
        fwrite(STDERR, 'runningInConsole='.var_export(app()->runningInConsole(), true)."\n");
        fwrite(STDERR, 'runningUnitTests='.var_export(app()->runningUnitTests(), true)."\n");
        fwrite(STDERR, 'config_app_env='.config('app.env')."\n");
        fwrite(STDERR, "ENV_DIAG end\n");

        $this->assertTrue(true);
    }
}
