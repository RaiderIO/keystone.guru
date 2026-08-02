<?php

namespace App\Logic\CombatLog\SpecialEvents;

use Override;

/**
 * EMOTE,Creature-0-4242-1841-14566-131318-00006285EA,"Elder Leaxa",0000000000000000,nil,|TINTERFACE\ICONS\INV_TikiMan2_Bloodtroll.blp:20|t Elder Leaxa begins to cast |cFFF00000|Hspell:264603|h[Blood Mirror]|h|r
 * The emote text is emitted unquoted, so any comma inside it is indistinguishable from a field separator and
 * the generic comma split yields more parameters than the event has fields:
 * EMOTE,Creature-0-3019-2519-13090-189340-00005D7992,"Chargath, Bane of Scales",Player-3684-0D90C5CC,"Novakk",|TInterface\Icons\Spell_Nature_Slow.blp:20|tChargath, Bane of Scales targets you with |cFFFF0000|Hspell:373424|h[Grounding Spear]|h|r!
 * EMOTE,Player-1427-0E8EA11C,"Baifrosth",Player-1427-0E8EA11C,"Baifrosth",Esto es patético, antiguo maestro, ni siquiera ese sucio demonio eredar recibe tantos golpes.
 * There is no upper bound on the amount of commas the text may contain, so the surplus parameters are folded
 * back into the emote text rather than the accepted parameter count being widened to fit.
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class Emote extends SpecialEvent
{
    private const int PARAMETER_COUNT = 5;

    /**
     * Skips the four leading fields - each either a quoted string, which may itself contain commas, or an
     * unquoted one - to recover the emote text from the raw line exactly as it was logged.
     */
    private const string EMOTE_TEXT_REGEX = '/(?:^|\s)EMOTE,(?:(?:"[^"]*"|[^,]*),){4}(.*)$/s';

    private string $sourceGuid;

    private string $sourceName;

    private string $destGuid;

    private string $destName;

    private string $emoteText;

    public function getSourceGuid(): string
    {
        return $this->sourceGuid;
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    public function getDestGuid(): string
    {
        return $this->destGuid;
    }

    /**
     * @return string The name of the target of the emote, or the literal 'nil' if the emote had no target.
     */
    public function getDestName(): string
    {
        return $this->destName;
    }

    public function getEmoteText(): string
    {
        return $this->emoteText;
    }

    /**
     * @param array<int, mixed> $parameters
     */
    #[Override]
    public function setParameters(array $parameters): self
    {
        $foldedParameters = $this->foldSurplusParametersIntoEmoteText($parameters);

        parent::setParameters($foldedParameters);

        $this->sourceGuid = (string)$foldedParameters[0];
        $this->sourceName = (string)$foldedParameters[1];
        $this->destGuid   = (string)$foldedParameters[2];
        $this->destName   = (string)$foldedParameters[3];
        $this->emoteText  = $this->parseEmoteTextFromRawEvent() ?? (string)$foldedParameters[4];

        return $this;
    }

    public function getParameterCount(): int
    {
        return self::PARAMETER_COUNT;
    }

    /**
     * The generic comma split cannot know where the unquoted emote text starts, so any parameters beyond the
     * expected count are surplus pieces of that text - fold them back into one so that the count validates.
     *
     * @param  array<int, mixed> $parameters
     * @return array<int, mixed>
     */
    private function foldSurplusParametersIntoEmoteText(array $parameters): array
    {
        if (count($parameters) <= self::PARAMETER_COUNT) {
            return $parameters;
        }

        return [
            ...array_slice($parameters, 0, self::PARAMETER_COUNT - 1),
            implode(',', array_map(strval(...), array_slice($parameters, self::PARAMETER_COUNT - 1))),
        ];
    }

    /**
     * @return string|null Null if the raw event did not have the expected shape, in which case the caller must
     *                     fall back to the re-joined parameters, which lose any whitespace directly following
     *                     a comma in the emote text.
     */
    private function parseEmoteTextFromRawEvent(): ?string
    {
        $matches = [];

        if (preg_match(self::EMOTE_TEXT_REGEX, $this->getRawEvent(), $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }
}
