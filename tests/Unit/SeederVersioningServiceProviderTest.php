<?php

namespace Thetheago\SeederVersioning\Tests\Unit;

use Thetheago\SeederVersioning\Facades\SeederVersioning;
use Thetheago\SeederVersioning\Services\SeederVersioningService;
use Thetheago\SeederVersioning\Tests\TestCase;

class SeederVersioningServiceProviderTest extends TestCase
{
    public function test_facadeAndServiceResolveToTheSameSingleton(): void
    {
        $this->assertSame(app('seeder-versioning'), app(SeederVersioningService::class));
        $this->assertSame(SeederVersioning::getFacadeRoot(), app(SeederVersioningService::class));
    }
}
