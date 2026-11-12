<?php

namespace Tests\Feature\Infrastructure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

/**
 * Infrastructure check for the route map in docs/IA.md: every route is named,
 * every parameter-free page renders, and planned actions say so honestly.
 */
class RouteMapTest extends TestCase
{
    use RefreshDatabase; // the sign-up page reads the roles table

    /** Framework routes that are not part of the product route map. */
    private const FRAMEWORK_URIS = ['up', 'storage/{path}'];

    /** @return array<int, Route> */
    private function applicationRoutes(): array
    {
        return array_values(array_filter(
            Router::getRoutes()->getRoutes(),
            fn (Route $route) => ! in_array($route->uri(), self::FRAMEWORK_URIS, true),
        ));
    }

    public function test_every_application_route_has_a_name(): void
    {
        $unnamed = array_map(
            fn (Route $route) => implode('|', $route->methods()).' /'.$route->uri(),
            array_filter($this->applicationRoutes(), fn (Route $route) => $route->getName() === null),
        );

        $this->assertSame([], array_values($unnamed), 'Name every route (see docs/IA.md conventions).');
    }

    public function test_every_parameter_free_page_renders(): void
    {
        $pages = array_filter(
            $this->applicationRoutes(),
            fn (Route $route) => in_array('GET', $route->methods(), true) && ! str_contains($route->uri(), '{'),
        );

        $this->assertNotEmpty($pages);

        // Visited as a guest: pages behind the auth middleware must send guests to the login page.
        foreach ($pages as $route) {
            $response = $this->get('/'.ltrim($route->uri(), '/'));

            in_array('auth', $route->gatherMiddleware(), true)
                ? $response->assertRedirect(route('login'))
                : $response->assertOk();
        }
    }

    public function test_planned_actions_answer_not_implemented(): void
    {
        $this->post(route('cart.items.store'))->assertStatus(501);
    }

    public function test_planned_page_shows_its_route(): void
    {
        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertSee('marketplace.index');
    }

    public function test_design_system_page_renders(): void
    {
        $this->get(route('design-system'))
            ->assertOk()
            ->assertSee('Design system v1.0');
    }
}
