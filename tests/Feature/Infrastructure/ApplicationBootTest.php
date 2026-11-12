<?php

namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

/**
 * Infrastructure check: the framework boots and serves its built-in health route.
 */
class ApplicationBootTest extends TestCase
{
    public function test_framework_health_route_responds(): void
    {
        $this->get('/up')->assertOk();
    }
}
