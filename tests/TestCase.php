<?php

namespace Thetheago\SeederVersioning\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends CreatesApplication
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defineEnvironment($this->app);
        $this->runTestMigrations();
    }
}
