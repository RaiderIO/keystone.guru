---
name: api-endpoint
description: Helps writing new public API v1 endpoints in a project-compliant way. Use when the user wants to add a new API route, controller, form request, resource, or test in the `app/Http/Controllers/Api/V1/` layer.
---

# API Endpoint

A skill for creating new public API v1 endpoints.

## Directory Layout

```
app/Http/Controllers/Api/V1/Public/{Group}/API{Name}Controller.php
app/Http/Requests/Api/V1/{Group}/{Name}Request.php
app/Http/Resources/{Group}/{Name}Resource.php          ← single item
app/Http/Resources/{Group}/{Name}EnvelopeResource.php  ← collection wrapper
routes/api.php                                         ← route registration
tests/Feature/Controller/Api/V1/API{Name}Controller/API{Name}ControllerTest.php
```

Reuse existing resources when they fit — `DungeonRouteSummaryResource` + `DungeonRouteSummaryEnvelopeResource` cover most route-listing responses.

## Form Request

Extends `App\Http\Requests\Api\V1\APIFormRequest`. Always:
- `authorize(): bool` → `true` for public endpoints
- `getRequestModelClass(): ?string` → `null` unless a typed request model is needed
- `rules(): array` — validated inputs

Use typed accessor methods on the request instead of sprinkling `?? default` throughout the controller.

### Offset pagination

For endpoints that take `offset` + `count` query params, use `APIOffsetPaginatedRequest` directly — no subclass needed unless you have extra rules:

```php
// routes/api.php
Route::get('popular', new APIDungeonRouteDiscoverController()->popular(...))->name('...');

// controller method signature
public function popular(
    APIOffsetPaginatedRequest $request,
    GameVersion               $gameVersion,
    DiscoverServiceInterface  $discoverService,
): DungeonRouteSummaryEnvelopeResource {
    // $request->getOffset() → int (default 0)
    // $request->getCount()  → int (default 10, max 100)
}
```

`APIOffsetPaginatedRequest` lives in `app/Http/Requests/Api/V1/APIOffsetPaginatedRequest.php`. Its rules:

```php
'offset' => ['nullable', 'integer', 'min:0'],
'count'  => ['nullable', 'integer', 'min:1', 'max:100'],
```

When an endpoint needs additional validation on top of pagination, create a dedicated request that extends `APIOffsetPaginatedRequest` and merge in extra rules:

```php
class MySpecialRequest extends APIOffsetPaginatedRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'dungeon_id' => ['nullable', 'integer', Rule::exists(Dungeon::class, 'id')],
        ]);
    }
}
```

For non-paginated endpoints, extend `APIFormRequest` directly instead.

## Controller

Extends `App\Http\Controllers\Controller`. Services are injected as method parameters — never constructor-injected.

Route model binding parameters come **before** service parameters in the method signature, matching the order they appear in the route URL.

```php
class APIDungeonRouteDiscoverController extends Controller
{
    /**
     * @OA\Get(
     *     operationId="getRoutesByGameVersion",
     *     path="/api/v1/routes/{gameVersion}/popular",
     *     summary="...",
     *     tags={"Route"},
     *     @OA\Parameter(name="gameVersion", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/DungeonRouteSummaryEnvelope")
     *     )
     * )
     */
    public function popular(
        DiscoverPaginatedRequest $request,
        GameVersion              $gameVersion,   // route model: resolved by key
        DiscoverServiceInterface $discoverService,
    ): DungeonRouteSummaryEnvelopeResource {
        return new DungeonRouteSummaryEnvelopeResource(
            $discoverService
                ->withCache(false)              // always for API — offset breaks cache key
                ->withGameVersion($gameVersion)
                ->withLimit($request->getCount())
                ->withBuilder(fn(Builder $b) => $b->offset($request->getOffset()))
                ->popular(),
        );
    }
}
```

**Cache:** Always call `->withCache(false)` when consuming `DiscoverServiceInterface` in API controllers. The cache key does not include `offset`, so paginated pages would collide.

## Route Registration

Routes live in `routes/api.php` inside `Route::prefix('v1')`. Use the first-class callable syntax:

```php
use App\Http\Controllers\Api\V1\Public\Route\APIDungeonRouteDiscoverController;

Route::prefix('routes/{gameVersion}')->group(static function () {
    Route::get('popular', new APIDungeonRouteDiscoverController()->popular(...))->name('api.v1.discover.popular');
    Route::prefix('{dungeon}')->group(static function () {
        Route::get('popular', new APIDungeonRouteDiscoverController()->dungeonPopular(...))->name('api.v1.discover.dungeon.popular');
    });
});
```

No middleware for public, unauthenticated endpoints. Add `middleware('throttle:...')` if the endpoint is write-heavy or expensive.

## Route Model Binding Cheat Sheet

| Model | Route parameter | Resolved by |
|---|---|---|
| `GameVersion` | `{gameVersion}` | `key` (e.g. `retail`, `wotlk`) |
| `Dungeon` | `{dungeon}` | `slug` (e.g. `ara-kara-city-of-echoes`) |
| `DungeonRoute` | `{dungeonRoute}` | `public_key` |
| `Expansion` | `{expansion}` | `shortname` (e.g. `tww`, `df`) |
| `Season` | `{season}` | `id` (default) |

## Resources

Collections use a `ResourceCollection` envelope; single items use `JsonResource`. Copy the naming convention from `app/Http/Resources/DungeonRoute/`.

```php
// Envelope (collection)
class DungeonRouteSummaryEnvelopeResource extends ResourceCollection
{
    public $collects = DungeonRouteSummaryResource::class;

    public function toArray(Request $request): array
    {
        return ['data' => $this->collection];
    }
}

// Item
class DungeonRouteSummaryResource extends JsonResource
{
    public function toArray(Request $request): array { /* ... */ }
}
```

## PHPUnit Test

Extends `PublicTestCase` for public (unauthenticated) endpoints; extends `APIPublicTestCase` for authenticated ones — the latter calls `$this->addAuthentication()` in `setUp()`.

**Always mock service dependencies** so tests never hit the real query logic:

```php
private function mockDiscoverService(): MockObject&DiscoverServiceInterface
{
    /** @var MockObject&DiscoverServiceInterface $mock */
    $mock = $this->createMockPublic(DiscoverServiceInterface::class);
    $mock->method('withCache')->willReturnSelf();
    $mock->method('withLimit')->willReturnSelf();
    $mock->method('withGameVersion')->willReturnSelf();
    $mock->method('withBuilder')->willReturnSelf();
    $mock->method('popular')->willReturn(new Collection());
    app()->instance(DiscoverServiceInterface::class, $mock);
    return $mock;
}
```

Bind the mock via `app()->instance(Interface::class, $mock)` — not via constructor injection.

Test naming: `methodName_givenCondition_shouldExpectedOutcome`.
Class attributes: `#[Group('Controller')]`, `#[Group('API')]`, `#[Group('API{Name}')]`.
Use `try/finally` to clean up any database records created inside a test.

```php
#[Group('Controller')]
#[Group('API')]
#[Group('APIDungeonRouteDiscover')]
final class APIDungeonRouteDiscoverControllerTest extends PublicTestCase
{
    #[Test]
    public function popular_givenValidGameVersion_shouldReturnOk(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstOrFail();
        $this->mockDiscoverService();

        // Act
        $response = $this->getJson(route('api.v1.discover.popular', ['gameVersion' => $gameVersion->key]));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    #[Test]
    public function popular_givenCountAboveMax_shouldReturn422(): void
    {
        // Arrange
        $this->mockDiscoverService();

        // Act
        $response = $this->getJson(route('api.v1.discover.popular', [
            'gameVersion' => GameVersion::firstOrFail()->key,
            'count'       => 101,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('data.count', fn($v) => !empty($v));
    }
}
```

## Checklist

After writing all files:
1. Run `docker compose exec -T app php artisan test --compact tests/Feature/Controller/Api/V1/API{Name}Controller/`
2. Run `docker compose exec -T app composer run fix` — may reformat the controller (alignment of parameter types)
3. Run `docker compose exec -T app composer run analyse`
4. Stage new files: `git add <files>`
