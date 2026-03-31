<?php

use Backstage\Redirects\Laravel\Models\Redirect;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Run migrations
    $this->artisan('migrate', ['--database' => 'testing'])->run();

    // Set up test route
    Route::get('/test-page', function () {
        return 'Test Page';
    })->middleware('web');

    Route::get('/destination-page', function () {
        return 'Destination Page';
    })->middleware('web');
});

it('redirects from path to path', function () {
    Redirect::create([
        'source' => '/pagina-1',
        'destination' => '/pagina-2',
        'code' => 301,
    ]);

    $response = $this->get('/pagina-1');

    $response->assertRedirect('/pagina-2');
    $response->assertStatus(301);
});

it('redirects from path without leading slash', function () {
    Redirect::create([
        'source' => 'pagina-1',
        'destination' => '/pagina-2',
        'code' => 301,
    ]);

    $response = $this->get('/pagina-1');

    $response->assertRedirect('/pagina-2');
});

it('redirects from path with leading slash', function () {
    Redirect::create([
        'source' => '/pagina-1',
        'destination' => '/pagina-2',
        'code' => 301,
    ]);

    $response = $this->get('pagina-1');

    $response->assertRedirect('/pagina-2');
});

it('increments hit counter on redirect', function () {
    $redirect = Redirect::create([
        'source' => '/pagina-1',
        'destination' => '/pagina-2',
        'code' => 301,
    ]);

    expect($redirect->hits)->toBe(0);

    $this->get('/pagina-1');

    expect($redirect->fresh()->hits)->toBe(1);

    $this->get('/pagina-1');

    expect($redirect->fresh()->hits)->toBe(2);
});

it('uses correct redirect code', function () {
    Redirect::create([
        'source' => '/pagina-1',
        'destination' => '/pagina-2',
        'code' => 302,
    ]);

    $response = $this->get('/pagina-1');

    $response->assertStatus(302);
});

it('redirects with 307 temporary redirect', function () {
    Redirect::create([
        'source' => '/pagina-1',
        'destination' => '/pagina-2',
        'code' => 307,
    ]);

    $response = $this->get('/pagina-1');

    $response->assertStatus(307);
});

it('redirects with 308 permanent redirect', function () {
    Redirect::create([
        'source' => '/pagina-1',
        'destination' => '/pagina-2',
        'code' => 308,
    ]);

    $response = $this->get('/pagina-1');

    $response->assertStatus(308);
});

it('does not redirect when source does not match', function () {
    Redirect::create([
        'source' => '/pagina-1',
        'destination' => '/pagina-2',
        'code' => 301,
    ]);

    Route::get('/different-page', function () {
        return 'Different Page';
    })->middleware('web');

    $response = $this->get('/different-page');

    $response->assertOk();
    $response->assertSee('Different Page');
});

it('preserves query parameters on redirect', function () {
    Redirect::create([
        'source' => '/pagina-1',
        'destination' => '/pagina-2',
        'code' => 301,
    ]);

    $response = $this->get('/pagina-1?foo=bar&baz=qux');

    $response->assertRedirect('/pagina-2?foo=bar&baz=qux');
});

it('handles redirects with special characters in path', function () {
    Redirect::create([
        'source' => '/pagina-met-streepjes',
        'destination' => '/nieuwe-pagina',
        'code' => 301,
    ]);

    $response = $this->get('/pagina-met-streepjes');

    $response->assertRedirect('/nieuwe-pagina');
});

it('only redirects on GET and HEAD requests', function () {
    Redirect::create([
        'source' => '/pagina-1',
        'destination' => '/pagina-2',
        'code' => 301,
    ]);

    // GET should redirect
    $getResponse = $this->get('/pagina-1');
    $getResponse->assertRedirect('/pagina-2');

    // HEAD should redirect
    $headResponse = $this->head('/pagina-1');
    $headResponse->assertRedirect('/pagina-2');

    // POST should not redirect
    Route::post('/pagina-1', function () {
        return 'POST Response';
    })->middleware('web');

    $postResponse = $this->post('/pagina-1');
    $postResponse->assertOk();
});

it('redirects to external URL', function () {
    Redirect::create([
        'source' => '/external-link',
        'destination' => 'https://example.com',
        'code' => 301,
    ]);

    $response = $this->get('/external-link');

    $response->assertRedirect('https://example.com');
});

it('handles multiple redirects and uses first match', function () {
    Redirect::create([
        'source' => '/pagina-1',
        'destination' => '/pagina-2',
        'code' => 301,
    ]);

    Redirect::create([
        'source' => '/pagina-1',
        'destination' => '/pagina-3',
        'code' => 302,
    ]);

    $response = $this->get('/pagina-1');

    // Should use first match
    $response->assertRedirect('/pagina-2');
    $response->assertStatus(301);
});
