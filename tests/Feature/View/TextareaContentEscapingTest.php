<?php

namespace Tests\Feature\View;

use App\Features\CreatorProfiles;
use App\Logic\Utils\HtmlSanitizer;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * Stored user text rendered inside a textarea is escaped once, so it cannot close the textarea and the browser
 * decodes it back to exactly what was stored.
 */
#[Group('View')]
final class TextareaContentEscapingTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string MARKUP_VALUE         = '</textarea><b>x</b>';
    private const string MARKUP_VALUE_ESCAPED = '&lt;/textarea&gt;&lt;b&gt;x&lt;/b&gt;';

    #[Test]
    #[DataProvider('routeDescriptionProvider')]
    public function edit_givenRouteDescription_returnsEscapedTextareaContent(string $value, string $expectedContent): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, $value);

        try {
            // Act
            $response = $this->getRouteEditPage($owner, $route);

            // Assert
            $response->assertOk();
            $this->assertTextareaContent((string)$response->getContent(), 'dungeon_route_description', $value, $expectedContent);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function routeDescriptionProvider(): array
    {
        return [
            'unsanitised closing textarea and markup' => [self::MARKUP_VALUE, self::MARKUP_VALUE_ESCAPED],
            'sanitised markup, & and quotes'          => ['Tom &amp; Jerry <b>bold</b> "hi" it\'s', 'Tom &amp; Jerry &lt;b&gt;bold&lt;/b&gt; &quot;hi&quot; it&#039;s'],
        ];
    }

    #[Test]
    #[DataProvider('typedDescriptionProvider')]
    public function edit_givenSanitisedRouteDescription_returnsTheTypedTextForEditing(string $typed): void
    {
        // Arrange
        $sanitizer = new HtmlSanitizer();
        $owner     = User::factory()->create();
        $route     = $this->createRoute($owner, $sanitizer->sanitize($typed));

        try {
            // Act
            $response = $this->getRouteEditPage($owner, $route);

            // Assert
            $response->assertOk();
            $shown = html_entity_decode($this->textareaContent((string)$response->getContent(), 'dungeon_route_description'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $this->assertSame($typed, $shown, 'The editor shows exactly what the user typed');
            $this->assertSame($route->description, $sanitizer->sanitize($shown), 'Saving the editor unchanged stores the same description');
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function typedDescriptionProvider(): array
    {
        return [
            'markup, & and quotes' => ['Tom & Jerry <b>bold</b> "hi" it\'s fine'],
            'non-ascii and a < b'  => ['Café < crème'],
        ];
    }

    #[Test]
    #[DataProvider('valueProvider')]
    public function edit_givenCollectionDescription_returnsEscapedTextareaContent(string $value, string $expectedContent): void
    {
        // Arrange
        $owner                  = $this->creator();
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'     => $owner->id,
            'description' => $value,
        ]);

        try {
            // Act
            $response = $this->actingAs($owner)->get(route('collection.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertOk();
            $this->assertTextareaContent((string)$response->getContent(), 'description', $value, $expectedContent);
        } finally {
            $dungeonRouteCollection->delete();
            $this->forgetCreator($owner);
        }
    }

    #[Test]
    public function edit_givenOldBioInput_returnsEscapedOldInput(): void
    {
        // Arrange
        $owner = $this->creator();
        $owner->update(['bio' => 'Stored bio']);

        try {
            // Act
            $response = $this->actingAs($owner)
                ->withSession(['_old_input' => ['bio' => self::MARKUP_VALUE]])
                ->get(route('profile.edit'));

            // Assert
            $response->assertOk();
            $this->assertTextareaContent((string)$response->getContent(), 'bio', self::MARKUP_VALUE, self::MARKUP_VALUE_ESCAPED);
        } finally {
            $this->forgetCreator($owner);
        }
    }

    #[Test]
    #[DataProvider('valueProvider')]
    public function edit_givenCreatorBio_returnsEscapedTextareaContent(string $value, string $expectedContent): void
    {
        // Arrange
        $owner = $this->creator();
        $owner->update(['bio' => $value]);

        try {
            // Act
            $response = $this->actingAs($owner)->get(route('profile.edit'));

            // Assert
            $response->assertOk();
            $this->assertTextareaContent((string)$response->getContent(), 'bio', $value, $expectedContent);
        } finally {
            $this->forgetCreator($owner);
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function valueProvider(): array
    {
        return [
            'closing textarea and markup'  => [self::MARKUP_VALUE, self::MARKUP_VALUE_ESCAPED],
            'plain text with & and quotes' => ['Tom & Jerry say "hi" & it\'s &amp; fine', 'Tom &amp; Jerry say &quot;hi&quot; &amp; it&#039;s &amp;amp; fine'],
        ];
    }

    private function assertTextareaContent(string $content, string $name, string $value, string $expectedContent): void
    {
        $this->assertMatchesRegularExpression(
            sprintf('/<textarea[^>]*name="%s"[^>]*>%s<\/textarea>/', preg_quote($name, '/'), preg_quote($expectedContent, '/')),
            $content,
            sprintf('The %s textarea holds the value escaped exactly once', $name),
        );
        $this->assertStringNotContainsString(
            sprintf('>%s', $value),
            $content,
            sprintf('The raw %s value is never written into the page', $name),
        );
    }

    private function textareaContent(string $content, string $name): string
    {
        $this->assertSame(1, preg_match(sprintf('/<textarea[^>]*name="%s"[^>]*>(.*?)<\/textarea>/s', preg_quote($name, '/')), $content, $matches));

        return $matches[1];
    }

    /**
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    private function getRouteEditPage(User $owner, DungeonRoute $route): TestResponse
    {
        return $this->actingAs($owner)->followingRedirects()->get(route('dungeonroute.edit', [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]));
    }

    private function createRoute(User $owner, string $description): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(
            facadeEnabled:       false,
            dungeonActive:       true,
            requireDefaultFloor: true,
        );

        return DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'description'        => $description,
        ]);
    }

    private function creator(): User
    {
        $creator = User::factory()->create();
        $creator->addRole(Role::ROLE_USER);
        Feature::for($creator)->activate(CreatorProfiles::class);

        return $creator;
    }

    private function forgetCreator(User $creator): void
    {
        Feature::for($creator)->forget(CreatorProfiles::class);
        $creator->delete();
    }
}
