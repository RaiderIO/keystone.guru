<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\Emote;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\Emote;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('Emote')]
final class EmoteTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('parseEvent_givenEmoteEvent_returnsCorrectValues_DataProvider')]
    public function parseEvent_givenEmoteEvent_returnsCorrectValues(
        string $emoteEvent,
        string $expectedSourceGuid,
        string $expectedSourceName,
        string $expectedDestGuid,
        string $expectedDestName,
        string $expectedEmoteText,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($emoteEvent);

        // Act
        /** @var Emote $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(Emote::class, $result);
        Assert::assertSame($result, $combatLogEntry->getParsedEvent());
        Assert::assertEquals($expectedSourceGuid, $result->getSourceGuid());
        Assert::assertEquals($expectedSourceName, $result->getSourceName());
        Assert::assertEquals($expectedDestGuid, $result->getDestGuid());
        Assert::assertEquals($expectedDestName, $result->getDestName());
        Assert::assertEquals($expectedEmoteText, $result->getEmoteText());
    }

    /**
     * @return array<string, list<string>>
     */
    public static function parseEvent_givenEmoteEvent_returnsCorrectValues_DataProvider(): array
    {
        return [
            // The emote text is unquoted, so every comma in it produces a surplus parameter that must be
            // folded back into the text - see #3790.
            'unquoted-text-with-two-commas' => [
                '7/23/2026 21:16:11.380-5  EMOTE,Player-1427-0E8EA11C,"Baifrosth",Player-1427-0E8EA11C,"Baifrosth",Esto es patético, antiguo maestro, ni siquiera ese sucio demonio eredar recibe tantos golpes.',
                'Player-1427-0E8EA11C',
                'Baifrosth',
                'Player-1427-0E8EA11C',
                'Baifrosth',
                'Esto es patético, antiguo maestro, ni siquiera ese sucio demonio eredar recibe tantos golpes.',
            ],
            'unquoted-text-with-three-commas' => [
                '7/23/2026 21:16:12.376-5  EMOTE,Player-1427-0E8EA11C,"Baifrosth",Player-1427-0E8EA11C,"Baifrosth",¡¡¡Atrápenlo, atrápenlo, atrápenlo, atrápenlo!!!',
                'Player-1427-0E8EA11C',
                'Baifrosth',
                'Player-1427-0E8EA11C',
                'Baifrosth',
                '¡¡¡Atrápenlo, atrápenlo, atrápenlo, atrápenlo!!!',
            ],
            'unquoted-text-without-commas' => [
                '7/23/2026 21:16:30.882-5  EMOTE,Player-1427-0E8EA11C,"Baifrosth",Player-1427-0E8EA11C,"Baifrosth",Daglop agita la cabeza.',
                'Player-1427-0E8EA11C',
                'Baifrosth',
                'Player-1427-0E8EA11C',
                'Baifrosth',
                'Daglop agita la cabeza.',
            ],
            'untargeted-emote-with-nil-dest-name' => [
                '3/25/2026 10:36:28.9051  EMOTE,Creature-0-4242-1841-14566-131318-00006285EA,"Elder Leaxa",0000000000000000,nil,|TINTERFACE\ICONS\INV_TikiMan2_Bloodtroll.blp:20|t Elder Leaxa begins to cast |cFFF00000|Hspell:264603|h[Blood Mirror]|h|r',
                'Creature-0-4242-1841-14566-131318-00006285EA',
                'Elder Leaxa',
                '0000000000000000',
                'nil',
                '|TINTERFACE\ICONS\INV_TikiMan2_Bloodtroll.blp:20|t Elder Leaxa begins to cast |cFFF00000|Hspell:264603|h[Blood Mirror]|h|r',
            ],
            // A comma inside the quoted source name must not be mistaken for a field separator, and the
            // bracketed spell link in the text must survive intact.
            'quoted-source-name-containing-a-comma' => [
                '3/25/2026 10:16:13.2761  EMOTE,Creature-0-3019-2519-13090-189340-00005D7992,"Chargath, Bane of Scales",Player-3684-0D90C5CC,"Novakk",|TInterface\Icons\Spell_Nature_Slow.blp:20|tChargath, Bane of Scales targets you with |cFFFF0000|Hspell:373424|h[Grounding Spear]|h|r!',
                'Creature-0-3019-2519-13090-189340-00005D7992',
                'Chargath, Bane of Scales',
                'Player-3684-0D90C5CC',
                'Novakk',
                '|TInterface\Icons\Spell_Nature_Slow.blp:20|tChargath, Bane of Scales targets you with |cFFFF0000|Hspell:373424|h[Grounding Spear]|h|r!',
            ],
            // A quoted emote text is split as a single parameter, so it must be unquoted like any other field
            // rather than kept verbatim from the raw line.
            'fully-quoted-emote-text' => [
                '3/25/2026 10:16:13.2761  EMOTE,Player-1427-0E8EA11C,"Baifrosth",Player-1427-0E8EA11C,"Baifrosth","Esto es patético, antiguo maestro."',
                'Player-1427-0E8EA11C',
                'Baifrosth',
                'Player-1427-0E8EA11C',
                'Baifrosth',
                'Esto es patético, antiguo maestro.',
            ],
            // An escaped quote inside a name is understood by CombatLogStringParser but not by the regex that
            // recovers the text from the raw line, so the two disagree and the parameters must win - at the
            // cost of the space that followed the comma.
            'escaped-quote-in-source-name-falls-back-to-parameters' => [
                '3/25/2026 10:16:13.2761  EMOTE,Creature-0-3019-2519-13090-189340-00005D7992,"Na\"me, X",Player-3684-0D90C5CC,"Novakk",Hello, there',
                'Creature-0-3019-2519-13090-189340-00005D7992',
                'Na\"me, X',
                'Player-3684-0D90C5CC',
                'Novakk',
                'Hello,there',
            ],
        ];
    }

    #[Test]
    public function setParameters_givenFewerParametersThanTheEventHasFields_throwsInvalidArgumentException(): void
    {
        // Arrange
        $rawEvent = '3/25/2026 10:16:13.2761  EMOTE,Player-1427-0E8EA11C,"Baifrosth",Player-1427-0E8EA11C';

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        new Emote(
            CombatLogVersion::RETAIL_12_0_1,
            Carbon::parse('2026-03-25 10:16:13'),
            SpecialEvent::SPECIAL_EVENT_EMOTE,
            ['Player-1427-0E8EA11C', 'Baifrosth', 'Player-1427-0E8EA11C'],
            $rawEvent,
        );
    }
}
