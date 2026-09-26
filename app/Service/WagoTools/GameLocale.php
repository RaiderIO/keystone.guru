<?php

namespace App\Service\WagoTools;

use Illuminate\Support\Str;

/**
 * A locale the game client - and therefore wago.tools' DB2 export - publishes its `_lang` columns in.
 *
 * Our own locales are the ones in `config('language.all')`; this maps them onto the game's, which is a
 * lossy mapping in both directions: the game has no Ukrainian client, and it has clients for locales we
 * do not offer.
 */
enum GameLocale: string
{
    case English            = 'enUS';
    case German             = 'deDE';
    case Spanish            = 'esES';
    case SpanishMexican     = 'esMX';
    case French             = 'frFR';
    case Italian            = 'itIT';
    case Korean             = 'koKR';
    case BrazilianPortugese = 'ptBR';
    case Russian            = 'ruRU';
    case ChineseSimplified  = 'zhCN';
    case ChineseTraditional = 'zhTW';

    /** Our locale that this game locale is the client data for, e.g. `de_DE`. */
    public function appLocale(): string
    {
        return match ($this) {
            self::English            => 'en_US',
            self::German             => 'de_DE',
            self::Spanish            => 'es_ES',
            self::SpanishMexican     => 'es_MX',
            self::French             => 'fr_FR',
            self::Italian            => 'it_IT',
            self::Korean             => 'ko_KR',
            self::BrazilianPortugese => 'pt_BR',
            self::Russian            => 'ru_RU',
            self::ChineseSimplified  => 'zh_CN',
            self::ChineseTraditional => 'zh_TW',
        };
    }

    /**
     * The game locale whose description a visitor on `$appLocale` should read.
     *
     * The AI variants of our locales (`de_DE_ai`) are the same language, so they read the same client
     * data; a locale the game has no client for - Ukrainian, Hodor - falls back to English, which is
     * what the site falls back to for those anyway.
     */
    public static function forAppLocale(?string $appLocale): self
    {
        $baseLocale = Str::replaceEnd('_ai', '', (string)$appLocale);

        foreach (self::cases() as $gameLocale) {
            if ($gameLocale->appLocale() === $baseLocale) {
                return $gameLocale;
            }
        }

        return self::English;
    }

    /**
     * Every game locale whose text has to be stored separately, i.e. all but the English one - English
     * descriptions live on the spells themselves.
     *
     * @return list<self>
     */
    public static function translated(): array
    {
        return array_values(array_filter(self::cases(), static fn(self $gameLocale): bool => $gameLocale !== self::English));
    }
}
