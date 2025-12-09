<?php

use App\Http\Middleware\Language\SetLocaleFixed;

return [

    /*
    |--------------------------------------------------------------------------
    | Enable All Language Routes
    |--------------------------------------------------------------------------
    |
    | This option enable language route.
    |
    */
    'route' => true,

    /*
    |--------------------------------------------------------------------------
    | Enable Language Home Route
    |--------------------------------------------------------------------------
    |
    | This option enable language route to set language and return
    | to url('/')
    |
    */
    'home' => true,

    /*
    |--------------------------------------------------------------------------
    | Add Language Code
    |--------------------------------------------------------------------------
    |
    | This option will add the language code to the redirected url
    |
    */
    'url' => false,

    /*
    |--------------------------------------------------------------------------
    | Set strategy
    |--------------------------------------------------------------------------
    |
    | This option will determine the strategy used to determine the back url.
    | It can be 'session' (default) or 'referer'
    |
    */
    'back' => 'referer',

    /*
    |--------------------------------------------------------------------------
    | Carbon Language
    |--------------------------------------------------------------------------
    |
    | This option the language of carbon library.
    |
    */
    'carbon' => true,

    /*
    |--------------------------------------------------------------------------
    | Date Language
    |--------------------------------------------------------------------------
    |
    | This option the language of jenssegers/date library.
    |
    */
    'date' => false,

    /*
    |--------------------------------------------------------------------------
    | Auto Change Language
    |--------------------------------------------------------------------------
    |
    | This option allows to change website language to user's
    | browser language.
    |
    */
    'auto' => true,

    /*
    |--------------------------------------------------------------------------
    | Routes Prefix
    |--------------------------------------------------------------------------
    |
    | This option indicates the prefix for language routes.
    |
    */
    'prefix' => 'languages',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | This option indicates the middleware to change language.
    |
    */
    'middleware' => SetLocaleFixed::class,

    /*
    |--------------------------------------------------------------------------
    | Controller
    |--------------------------------------------------------------------------
    |
    | This option indicates the controller to be used.
    |
    */
    'controller' => \Akaunting\Language\Controllers\Language::class,

    /*
    |--------------------------------------------------------------------------
    | Flags
    |--------------------------------------------------------------------------
    |
    | This option indicates the flags features.
    |
    */

    'flags' => ['width' => '22px', 'ul_class' => '', 'li_class' => '', 'img_class' => ''],

    /*
    |--------------------------------------------------------------------------
    | Language code mode
    |--------------------------------------------------------------------------
    |
    | This option indicates the language code and name to be used, short/long
    | and english/native.
    | Short: language code (en)
    | Long: languagecode_COUNTRYCODE (en_GB)
    |
    */

    'mode' => ['code' => 'long', 'name' => 'native'],

    /*
    |--------------------------------------------------------------------------
    | Allowed languages
    |--------------------------------------------------------------------------
    |
    | This options indicates the language allowed languages.
    |
    */
    'allowed' => env('APP_DEBUG') ?
        ['en_US', 'de_DE', 'es_ES', 'es_MX', 'fr_FR', 'ho_HO', 'it_IT', 'ko_KR', 'pt_BR', 'ru_RU', 'uk_UA'] :
        ['en_US', 'de_DE', 'es_ES', 'es_MX', 'fr_FR', 'it_IT', 'ko_KR', 'pt_BR', 'ru_RU', 'uk_UA'],

    /*
    |--------------------------------------------------------------------------
    | All Languages
    |--------------------------------------------------------------------------
    |
    | This option indicates the language codes and names.
    |
    */

    'all' => [
        //        ['short' => 'ar', 'long' => 'ar_SA', 'english' => 'Arabic', 'native' => 'العربية'],
        //        ['short' => 'bg', 'long' => 'bg_BG', 'english' => 'Bulgarian', 'native' => 'български'],
        //        ['short' => 'bn', 'long' => 'bn_BD', 'english' => 'Bengali', 'native' => 'বাংলা'],
        ['short' => 'cn', 'long' => 'zh_CN', 'english' => 'Chinese (S)', 'native' => '简体中文', 'ai' => true],
        //        ['short' => 'cs', 'long' => 'cs_CZ', 'english' => 'Czech', 'native' => 'Čeština'],
        //        ['short' => 'da', 'long' => 'da_DK', 'english' => 'Danish', 'native' => 'Dansk'],
        ['short' => 'de', 'long' => 'de_DE', 'english' => 'German', 'native' => 'Deutsch', 'ai' => true],
        //        ['short' => 'de', 'long' => 'de_AT', 'english' => 'Austrian', 'native' => 'Österreichisches Deutsch'],
        //        ['short' => 'fi', 'long' => 'fi_FI', 'english' => 'Finnish', 'native' => 'Suomi'],
        ['short' => 'fr', 'long' => 'fr_FR', 'english' => 'French', 'native' => 'Français', 'ai' => true],
        //        ['short' => 'el', 'long' => 'el_GR', 'english' => 'Greek', 'native' => 'Ελληνικά'],
        //        ['short' => 'en', 'long' => 'en_AU', 'english' => 'English (AU)', 'native' => 'English (AU)'],
        //        ['short' => 'en', 'long' => 'en_CA', 'english' => 'English (CA)', 'native' => 'English (CA)'],
        //        ['short' => 'en', 'long' => 'en_GB', 'english' => 'English (GB)', 'native' => 'English (GB)'],
        ['short' => 'en', 'long' => 'en_US', 'english' => 'English (US)', 'native' => 'English (US)'],
        ['short' => 'es', 'long' => 'es_ES', 'english' => 'Spanish', 'native' => 'Español', 'ai' => true],
        //        ['short' => 'et', 'long' => 'et_EE', 'english' => 'Estonian', 'native' => 'Eesti'],
        //        ['short' => 'he', 'long' => 'he_IL', 'english' => 'Hebrew', 'native' => 'עִבְרִית'],
        //        ['short' => 'hi', 'long' => 'hi_IN', 'english' => 'Hindi', 'native' => 'हिन्दी'],
        ['short' => 'ho', 'long' => 'ho_HO', 'english' => 'Hodor', 'native' => 'Hodor'],
        //        ['short' => 'hr', 'long' => 'hr_HR', 'english' => 'Croatian', 'native' => 'Hrvatski'],
        //        ['short' => 'hu', 'long' => 'hu_HU', 'english' => 'Hungarian', 'native' => 'Magyar'],
        //        ['short' => 'hy', 'long' => 'hy_AM', 'english' => 'Armenian', 'native' => 'Հայերեն'],
        //        ['short' => 'id', 'long' => 'id_ID', 'english' => 'Indonesian', 'native' => 'Bahasa Indonesia'],
        ['short' => 'it', 'long' => 'it_IT', 'english' => 'Italian', 'native' => 'Italiano', 'ai' => true],
        //        ['short' => 'ir', 'long' => 'fa_IR', 'english' => 'Persian', 'native' => 'فارسی'],
        //        ['short' => 'jp', 'long' => 'ja_JP', 'english' => 'Japanese', 'native' => '日本語'],
        //        ['short' => 'ka', 'long' => 'ka_GE', 'english' => 'Georgian', 'native' => 'ქართული'],
        ['short' => 'ko', 'long' => 'ko_KR', 'english' => 'Korean', 'native' => '한국어', 'ai' => true],
        //        ['short' => 'lt', 'long' => 'lt_LT', 'english' => 'Lithuanian', 'native' => 'Lietuvių'],
        //        ['short' => 'lv', 'long' => 'lv_LV', 'english' => 'Latvian', 'native' => 'Latviešu valoda'],
        //        ['short' => 'mk', 'long' => 'mk_MK', 'english' => 'Macedonian', 'native' => 'Македонски јазик'],
        //        ['short' => 'ms', 'long' => 'ms_MY', 'english' => 'Malay', 'native' => 'Bahasa Melayu'],
        ['short' => 'mx', 'long' => 'es_MX', 'english' => 'Mexico', 'native' => 'Español de México', 'ai' => true],
        //        ['short' => 'nb', 'long' => 'nb_NO', 'english' => 'Norwegian', 'native' => 'Norsk Bokmål'],
        //        ['short' => 'ne', 'long' => 'ne_NP', 'english' => 'Nepali', 'native' => 'नेपाली'],
        //        ['short' => 'nl', 'long' => 'nl_NL', 'english' => 'Dutch', 'native' => 'Nederlands'],
        //        ['short' => 'pl', 'long' => 'pl_PL', 'english' => 'Polish', 'native' => 'Polski'],
        ['short' => 'pt', 'long' => 'pt_BR', 'english' => 'Brazilian', 'native' => 'Português do Brasil', 'ai' => true],
        //        ['short' => 'pt', 'long' => 'pt_PT', 'english' => 'Portuguese', 'native' => 'Português'],
        //        ['short' => 'ro', 'long' => 'ro_RO', 'english' => 'Romanian', 'native' => 'Română'],
        ['short' => 'ru', 'long' => 'ru_RU', 'english' => 'Russian', 'native' => 'Русский', 'ai' => true],
        //        ['short' => 'sr', 'long' => 'sr_RS', 'english' => 'Serbian (Cyrillic)', 'native' => 'Српски језик'],
        //        ['short' => 'sr', 'long' => 'sr_CS', 'english' => 'Serbian (Latin)', 'native' => 'Српски језик'],
        //        ['short' => 'sq', 'long' => 'sq_AL', 'english' => 'Albanian', 'native' => 'Shqip'],
        //        ['short' => 'sk', 'long' => 'sk_SK', 'english' => 'Slovak', 'native' => 'Slovenčina'],
        //        ['short' => 'sl', 'long' => 'sl_SL', 'english' => 'Slovenian', 'native' => 'Slovenščina'],
        //        ['short' => 'sv', 'long' => 'sv_SE', 'english' => 'Swedish', 'native' => 'Svenska'],
        //        ['short' => 'th', 'long' => 'th_TH', 'english' => 'Thai', 'native' => 'ไทย'],
        //        ['short' => 'tr', 'long' => 'tr_TR', 'english' => 'Turkish', 'native' => 'Türkçe'],
        ['short' => 'tw', 'long' => 'zh_TW', 'english' => 'Chinese (T)', 'native' => '繁體中文', 'ai' => true],
        ['short' => 'uk', 'long' => 'uk_UA', 'english' => 'Ukrainian', 'native' => 'Українська'],
        //        ['short' => 'ur', 'long' => 'ur_PK', 'english' => 'Urdu (Pakistan)', 'native' => 'اردو'],
        //        ['short' => 'uz', 'long' => 'uz_UZ', 'english' => 'Uzbek', 'native' => "O'zbek"],
        //        ['short' => 'vi', 'long' => 'vi_VN', 'english' => 'Vietnamese', 'native' => 'Tiếng Việt'],
    ],
];
