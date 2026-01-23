# Laravel Redirects

[![Latest Version on Packagist](https://img.shields.io/packagist/v/backstage/laravel-redirects.svg?style=flat-square)](https://packagist.org/packages/backstage/laravel-redirects)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/backstagephp/laravel-redirects/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/backstagephp/laravel-redirects/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/backstagephp/laravel-redirects/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/backstagephp/laravel-redirects/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/backstage/laravel-redirects.svg?style=flat-square)](https://packagist.org/packages/backstage/laravel-redirects)

A powerful and flexible Laravel package for managing HTTP redirects through a database-driven approach. Dynamically create, update, and track redirects without modifying code or redeploying your application.

## Features

- **Database-driven redirects** - Manage redirects dynamically without code changes
- **Multiple matching strategies** - HTTP, wildcard, and strict matching middleware
- **Status code support** - 301, 302, 307, 308 redirects
- **Query string preservation** - Automatically maintains query parameters
- **Hit tracking** - Monitor redirect usage with built-in analytics
- **Event-driven automation** - Automatically create redirects when URLs change
- **SEO-friendly** - Proper HTTP status codes and trailing slash handling
- **Case sensitivity control** - Configure case-sensitive or insensitive matching
- **Trailing slash handling** - Flexible trailing slash sensitivity options
- **Protocol agnostic** - Works with HTTP and HTTPS seamlessly

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Creating Redirects](#creating-redirects)
  - [Automatic Redirects via Events](#automatic-redirects-via-events)
  - [Query String Handling](#query-string-handling)
  - [Tracking Redirect Hits](#tracking-redirect-hits)
- [Middleware](#middleware)
  - [HttpRedirects](#httpredirects)
  - [WildRedirects](#wildredirects)
  - [StrictRedirects](#strictredirects)
- [Advanced Usage](#advanced-usage)
  - [Custom Redirect Model](#custom-redirect-model)
  - [Managing Redirects Programmatically](#managing-redirects-programmatically)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security](#security)
- [Credits](#credits)
- [License](#license)

## Installation

You can install the package via Composer:

```bash
composer require backstage/laravel-redirects
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="laravel-redirects-migrations"
php artisan migrate
```

This will create a `redirects` table with the following structure:

| Column | Type | Description |
|--------|------|-------------|
| ulid | ULID | Primary key |
| source | string | The source URL to redirect from |
| destination | string | The destination URL to redirect to |
| code | integer | HTTP status code (301, 302, 307, 308) |
| hits | integer | Number of times this redirect was triggered |
| created_at | timestamp | When the redirect was created |
| updated_at | timestamp | When the redirect was last modified |

Optionally, publish the configuration file:

```bash
php artisan vendor:publish --tag="laravel-redirects-config"
```

## Configuration

The configuration file `config/redirects.php` provides extensive customization options:

```php
return [
    /*
     * Available HTTP status codes for redirection.
     * Uncomment additional codes as needed.
     */
    'status_codes' => [
        301 => 'Moved Permanently',      // Permanent redirect, cached by browsers
        302 => 'Found',                  // Temporary redirect, not cached
        307 => 'Temporary Redirect',     // Temporary, maintains HTTP method
        308 => 'Permanent Redirect',     // Permanent, maintains HTTP method
    ],

    /*
     * The model to use for managing redirects.
     * Override this to use your own custom model.
     */
    'model' => Backstage\Redirects\Laravel\Models\Redirect::class,

    /*
     * Default status code for new redirects.
     * Can be overridden via REDIRECT_DEFAULT_STATUS_CODE env variable.
     */
    'default_status_code' => env('REDIRECT_DEFAULT_STATUS_CODE', 301),

    /*
     * Case sensitivity for URL matching.
     *
     * false: /example and /Example are treated as the same
     * true:  /example and /Example are treated as different URLs
     */
    'case_sensitive' => env('REDIRECT_CASE_SENSITIVE', false),

    /*
     * Trailing slash sensitivity for URL matching.
     *
     * false: /example and /example/ are treated as the same
     * true:  /example and /example/ are treated as different URLs
     */
    'trailing_slash_sensitive' => env('REDIRECT_TRAILING_SLASH_SENSITIVE', false),

    /*
     * Add trailing slash to redirect destinations.
     * Useful for maintaining URL consistency and SEO.
     */
    'trailing_slash' => env('REDIRECT_WITH_TRAILING_SLASH', false),

    /*
     * Middleware stack for handling redirects.
     * Order matters - first match wins.
     *
     * - HttpRedirects: Matches URLs with protocol/www variations
     * - WildRedirects: Partial URL matching (contains)
     * - StrictRedirects: Exact URL matching
     */
    'middleware' => [
        Backstage\Redirects\Laravel\Http\Middleware\HttpRedirects::class,
        Backstage\Redirects\Laravel\Http\Middleware\WildRedirects::class,
        Backstage\Redirects\Laravel\Http\Middleware\StrictRedirects::class,
    ],
];
```

### Environment Variables

Add these to your `.env` file for environment-specific configuration:

```env
REDIRECT_DEFAULT_STATUS_CODE=301
REDIRECT_CASE_SENSITIVE=false
REDIRECT_TRAILING_SLASH_SENSITIVE=false
REDIRECT_WITH_TRAILING_SLASH=false
```

## Usage

### Creating Redirects

#### Database Seeder

```php
use Backstage\Redirects\Laravel\Models\Redirect;

Redirect::create([
    'source' => '/old-page',
    'destination' => '/new-page',
    'code' => 301,
]);
```

#### Programmatically

```php
use Backstage\Redirects\Laravel\Models\Redirect;

// Permanent redirect (301)
Redirect::create([
    'source' => '/old-blog-post',
    'destination' => '/new-blog-post',
    'code' => 301,
]);

// Temporary redirect (302)
Redirect::create([
    'source' => '/maintenance',
    'destination' => '/under-construction',
    'code' => 302,
]);

// Wildcard redirect (matches any URL containing the source)
Redirect::create([
    'source' => '/blog/category/',
    'destination' => '/articles/',
    'code' => 301,
]);
```

#### Via Tinker

```bash
php artisan tinker
```

```php
Redirect::create([
    'source' => 'example.com/old-url',
    'destination' => 'example.com/new-url',
    'code' => 301,
]);
```

### Automatic Redirects via Events

The package includes an event-listener system to automatically create redirects when URLs change. This is useful when updating slugs or moving content:

```php
use Backstage\Redirects\Laravel\Events\UrlHasChanged;

// When a blog post URL changes
event(new UrlHasChanged(
    oldUrl: 'https://example.com/old-slug',
    newUrl: 'https://example.com/new-slug',
    code: 301
));
```

This automatically creates a redirect in the database:

```php
// Created automatically by the listener
Redirect::create([
    'source' => 'https://example.com/old-slug',
    'destination' => 'https://example.com/new-slug',
    'code' => 301,
]);
```

**Integration Example with Eloquent Models:**

```php
use Backstage\Redirects\Laravel\Events\UrlHasChanged;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected static function booted()
    {
        static::updating(function ($post) {
            if ($post->isDirty('slug')) {
                $oldUrl = route('blog.show', $post->getOriginal('slug'));
                $newUrl = route('blog.show', $post->slug);

                event(new UrlHasChanged($oldUrl, $newUrl, 301));
            }
        });
    }
}
```

### Query String Handling

The package automatically preserves query strings from the source URL and appends them to the destination:

```php
Redirect::create([
    'source' => '/old-page',
    'destination' => '/new-page',
    'code' => 301,
]);
```

When a user visits:
```
/old-page?utm_source=email&utm_campaign=newsletter
```

They are redirected to:
```
/new-page?utm_source=email&utm_campaign=newsletter
```

If the destination already has query parameters:

```php
Redirect::create([
    'source' => '/old-page',
    'destination' => '/new-page?foo=bar',
    'code' => 301,
]);
```

Visiting `/old-page?baz=qux` redirects to:
```
/new-page?foo=bar&baz=qux
```

### Tracking Redirect Hits

Every time a redirect is triggered, the `hits` counter increments automatically:

```php
$redirect = Redirect::where('source', '/old-page')->first();
echo $redirect->hits; // Number of times this redirect was used
```

Use this data for analytics and monitoring:

```php
// Most used redirects
$popular = Redirect::orderBy('hits', 'desc')->take(10)->get();

// Recently created redirects
$recent = Redirect::latest()->take(10)->get();

// Unused redirects (candidates for removal)
$unused = Redirect::where('hits', 0)->get();
```

## Middleware

The package includes three middleware classes, each with different matching strategies. They run in the order defined in `config/redirects.php`.

### HttpRedirects

Matches URLs with protocol and `www` variations normalized:

```php
Redirect::create([
    'source' => 'example.com/page',
    'destination' => 'example.com/new-page',
    'code' => 301,
]);
```

This matches all of these URLs:
- `http://example.com/page`
- `https://example.com/page`
- `http://www.example.com/page`
- `https://www.example.com/page`

### WildRedirects

Performs partial URL matching using `contains()`:

```php
Redirect::create([
    'source' => '/blog/',
    'destination' => '/articles/',
    'code' => 301,
]);
```

This matches:
- `/blog/post-1` → `/articles/`
- `/blog/category/tech` → `/articles/`
- `/old-blog/archive` → `/articles/`

### StrictRedirects

Exact URL matching without query strings:

```php
Redirect::create([
    'source' => 'example.com/exact-page',
    'destination' => 'example.com/new-exact-page',
    'code' => 301,
]);
```

This only matches:
- `http://example.com/exact-page`
- `https://example.com/exact-page`
- `http://www.example.com/exact-page`

But NOT:
- `example.com/exact-page/sub-page`
- `example.com/other-exact-page`

### Customizing Middleware Order

The middleware runs in the order defined in your config. First match wins:

```php
'middleware' => [
    // 1. Try HTTP matching first (protocol/www normalized)
    Backstage\Redirects\Laravel\Http\Middleware\HttpRedirects::class,

    // 2. Then wildcard matching
    Backstage\Redirects\Laravel\Http\Middleware\WildRedirects::class,

    // 3. Finally exact matching
    Backstage\Redirects\Laravel\Http\Middleware\StrictRedirects::class,
],
```

You can reorder or remove middleware as needed. For example, to only use exact matching:

```php
'middleware' => [
    Backstage\Redirects\Laravel\Http\Middleware\StrictRedirects::class,
],
```

## Advanced Usage

### Custom Redirect Model

Create your own model extending the base Redirect model:

```php
namespace App\Models;

use Backstage\Redirects\Laravel\Models\Redirect as BaseRedirect;

class Redirect extends BaseRedirect
{
    // Add custom scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    // Add relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Override redirect logic
    public function redirect(Request $request): ?RedirectResponse
    {
        // Custom logic before redirect
        \Log::info("Redirecting from {$this->source} to {$this->destination}");

        return parent::redirect($request);
    }
}
```

Update your config:

```php
'model' => App\Models\Redirect::class,
```

### Managing Redirects Programmatically

**Bulk Creation:**

```php
$redirects = [
    ['source' => '/old-1', 'destination' => '/new-1', 'code' => 301],
    ['source' => '/old-2', 'destination' => '/new-2', 'code' => 301],
    ['source' => '/old-3', 'destination' => '/new-3', 'code' => 301],
];

foreach ($redirects as $redirect) {
    Redirect::create($redirect);
}
```

**Import from CSV:**

```php
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

$csv = Reader::createFromPath(Storage::path('redirects.csv'));
$csv->setHeaderOffset(0);

foreach ($csv->getRecords() as $record) {
    Redirect::create([
        'source' => $record['source'],
        'destination' => $record['destination'],
        'code' => $record['code'] ?? 301,
    ]);
}
```

**Conditional Redirects:**

```php
// Only redirect if destination exists
if (Route::has('new-route')) {
    Redirect::create([
        'source' => '/old-route',
        'destination' => route('new-route'),
        'code' => 301,
    ]);
}
```

**Redirect Chains (avoid these):**

```php
// BAD: Creates a redirect chain
// /page-1 → /page-2 → /page-3
Redirect::create(['source' => '/page-1', 'destination' => '/page-2', 'code' => 301]);
Redirect::create(['source' => '/page-2', 'destination' => '/page-3', 'code' => 301]);

// GOOD: Direct redirect
Redirect::create(['source' => '/page-1', 'destination' => '/page-3', 'code' => 301]);
```

## API Reference

### Redirect Model

**Properties:**

- `ulid` (string) - Primary key
- `source` (string) - Source URL
- `destination` (string) - Destination URL
- `code` (int) - HTTP status code
- `hits` (int) - Number of redirects performed
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Methods:**

```php
// Perform the redirect
public function redirect(Request $request): ?RedirectResponse

// Increment hits counter (called automatically)
public function increment('hits'): void
```

### Events

**UrlHasChanged Event:**

```php
use Backstage\Redirects\Laravel\Events\UrlHasChanged;

event(new UrlHasChanged(
    oldUrl: 'https://example.com/old',
    newUrl: 'https://example.com/new',
    code: 301 // Optional, defaults to 301
));
```

**Properties:**
- `oldUrl` (string) - The old URL
- `newUrl` (string) - The new URL
- `code` (int) - HTTP status code (default: 301)

### Listeners

**RedirectOldUrlToNewUrl Listener:**

Automatically creates a redirect when `UrlHasChanged` event is dispatched.

## HTTP Status Codes

Understanding when to use each status code:

| Code | Name | Use Case | Cached by Browsers |
|------|------|----------|-------------------|
| 301 | Moved Permanently | Permanent content relocation, old URL will never be used again | Yes |
| 302 | Found | Temporary redirect, old URL may be used again | No |
| 307 | Temporary Redirect | Temporary redirect that preserves HTTP method (POST stays POST) | No |
| 308 | Permanent Redirect | Permanent redirect that preserves HTTP method (POST stays POST) | Yes |

**Recommendations:**

- Use **301** for most permanent redirects (blog posts, pages, renamed resources)
- Use **302** for temporary situations (maintenance pages, A/B testing)
- Use **307** when redirecting form submissions temporarily
- Use **308** when permanently moving an API endpoint that receives POST/PUT/DELETE requests

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

Run static analysis:

```bash
composer analyse
```

Fix code style:

```bash
composer format
```

**Writing Tests:**

```php
use Backstage\Redirects\Laravel\Models\Redirect;

it('redirects old URL to new URL', function () {
    Redirect::create([
        'source' => '/old',
        'destination' => '/new',
        'code' => 301,
    ]);

    $response = $this->get('/old');

    $response->assertRedirect('/new');
    $response->assertStatus(301);
});

it('preserves query strings', function () {
    Redirect::create([
        'source' => '/old',
        'destination' => '/new',
        'code' => 301,
    ]);

    $response = $this->get('/old?foo=bar');

    $response->assertRedirect('/new?foo=bar');
});

it('increments hits counter', function () {
    $redirect = Redirect::create([
        'source' => '/old',
        'destination' => '/new',
        'code' => 301,
    ]);

    expect($redirect->hits)->toBe(0);

    $this->get('/old');

    expect($redirect->fresh()->hits)->toBe(1);
});
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details on how to contribute to this package.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

**Security Considerations:**

- Validate redirect destinations to prevent open redirects
- Sanitize user input when creating redirects programmatically
- Monitor for redirect loops and chains
- Implement rate limiting to prevent abuse
- Use HTTPS for all redirect destinations when possible

## Credits

- [Mark van Eijk](https://github.com/markvaneijk) - Creator and maintainer
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## Support

- [Documentation](https://github.com/backstagephp/laravel-redirects)
- [Issue Tracker](https://github.com/backstagephp/laravel-redirects/issues)
- [Discussions](https://github.com/backstagephp/laravel-redirects/discussions)

Built with by [Backstage CMS](https://backstagephp.com)
