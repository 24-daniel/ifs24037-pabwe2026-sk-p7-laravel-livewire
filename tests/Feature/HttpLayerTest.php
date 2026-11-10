<?php

namespace Tests\Feature;

use App\Features\Stock\Models\Stock;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureStockOwnership;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class HttpLayerTest extends TestCase
{
    use RefreshDatabase;

    private function req(?User $user): Request
    {
        $request = Request::create('/x');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_role_middleware(): void
    {
        $mw = new EnsureRole;
        $next = fn () => new Response('ok');

        $this->assertSame('ok', $mw->handle($this->req(User::factory()->make(['role' => 'member'])), $next, 'member')->getContent());

        $this->expectException(HttpException::class);
        $mw->handle($this->req(null), $next, 'member');
    }

    public function test_role_middleware_wrong_role(): void
    {
        $this->expectException(HttpException::class);
        (new EnsureRole)->handle($this->req(User::factory()->make(['role' => 'admin'])), fn () => new Response('ok'), 'member');
    }

    public function test_ownership_middleware_branches(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $owner->id]);
        $mw = new EnsureStockOwnership;
        $next = fn () => new Response('ok');

        // route('stock') sebagai model
        $r = $this->req($owner);
        $r->setRouteResolver(fn () => new class($stock)
        {
            public function __construct(private $s) {}

            public function parameter($k, $d = null)
            {
                return $this->s;
            }
        });
        $this->assertSame('ok', $mw->handle($r, $next)->getContent());

        // route('stock') sebagai id numerik
        $r = $this->req($owner);
        $r->setRouteResolver(fn () => new class($stock->id)
        {
            public function __construct(private $s) {}

            public function parameter($k, $d = null)
            {
                return (string) $this->s;
            }
        });
        $this->assertSame('ok', $mw->handle($r, $next)->getContent());

        // bukan pemilik -> 403
        $r = $this->req($other);
        $r->setRouteResolver(fn () => new class($stock->id)
        {
            public function __construct(private $s) {}

            public function parameter($k, $d = null)
            {
                return $this->s;
            }
        });
        try {
            $mw->handle($r, $next);
            $this->fail('harus 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // tanpa user -> 403
        $r = $this->req(null);
        $r->setRouteResolver(fn () => new class($stock)
        {
            public function __construct(private $s) {}

            public function parameter($k, $d = null)
            {
                return $this->s;
            }
        });
        $this->expectException(HttpException::class);
        $mw->handle($r, $next);
    }

    public function test_ownership_middleware_non_numeric_id_is_404(): void
    {
        $r = $this->req(User::factory()->create());
        $r->setRouteResolver(fn () => new class
        {
            public function parameter($k, $d = null)
            {
                return 'abc';
            }
        });
        $this->expectException(NotFoundHttpException::class);
        (new EnsureStockOwnership)->handle($r, fn () => new Response('ok'));
    }

    public function test_routes_and_access_control(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->create(['user_id' => $member->id]);

        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/stocks')->assertRedirect('/login');

        $this->actingAs($member)->get('/login')->assertRedirect();
        $this->actingAs($member)->get('/stocks')->assertOk();
        $this->actingAs($member)->get('/profile')->assertOk();
        $this->actingAs($member)->get("/stocks/{$stock->id}")->assertOk();
        $this->actingAs($member)->get('/admin/users')->assertForbidden();
        $this->actingAs($other)->get("/stocks/{$stock->id}")->assertForbidden();

        $this->actingAs($admin)->get('/stocks')->assertForbidden();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get("/admin/users/{$member->id}/stocks")->assertOk();
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_provider_forces_https_and_binds(): void
    {
        config(['security.force_https' => true]);
        (new AppServiceProvider($this->app))->boot();
        $this->assertStringStartsWith('https://', URL::to('/x'));
    }
}
