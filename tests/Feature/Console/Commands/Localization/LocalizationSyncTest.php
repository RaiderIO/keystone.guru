<?php

namespace Tests\Feature\Console\Commands\Localization;

use App\Console\Commands\Localization\LocalizationSync;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Localization')]
class LocalizationSyncTest extends PublicTestCase
{
    private const string BASE = <<<'PHP'
<?php

return [
    'with_apostrophe' => "Keystone Guru: Skycap'n Kragg flees.",
    'with_quote'      => 'He said "go".',
    'plain'           => 'Plain',
    'added'           => 'Added in en_US',
];
PHP;

    private const string TARGET = <<<'PHP'
<?php

return [
    'with_apostrophe' => 'Keystone Guru: Skycap\'n Kragg flieht.',
    'with_quote'      => "Er sagte \"los\".",
    'plain'           => 'Einfach',
];
PHP;

    #[Test]
    public function parse_givenLemmaQuotedDifferentlyFromBase_keepsTheLemmaQuoteStyle(): void
    {
        // Arrange
        $command = new LocalizationSync();

        // Act
        $lemmas = $command->parse('de_DE', self::TARGET);
        $result = $command->parse('de_DE', self::BASE, $lemmas);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString("'with_apostrophe' => 'Keystone Guru: Skycap\\'n Kragg flieht.',", $result);
        $this->assertStringContainsString("'with_quote'      => \"Er sagte \\\"los\\\".\",", $result);
        $this->assertStringContainsString("'plain'           => 'Einfach',", $result);
        // A key the target lacks becomes an empty stub in the base's quote style
        $this->assertStringContainsString("'added'           => '',", $result);
    }

    #[Test]
    public function parse_givenSyncedOutput_evaluatesToTheSameValuesAsTheTarget(): void
    {
        // Arrange
        $command = new LocalizationSync();
        $lemmas  = $command->parse('de_DE', self::TARGET);
        $result  = $command->parse('de_DE', self::BASE, $lemmas);

        // Act
        $target = eval(substr(self::TARGET, strlen('<?php')));
        $synced = eval(substr((string)$result, strlen('<?php')));

        // Assert
        $this->assertSame($target['with_apostrophe'], $synced['with_apostrophe']);
        $this->assertSame($target['with_quote'], $synced['with_quote']);
        $this->assertSame($target['plain'], $synced['plain']);
        $this->assertSame('', $synced['added']);
    }
}
