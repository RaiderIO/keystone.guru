<?php

namespace App\Console\Commands\Localization;

use Illuminate\Console\Command;

/**
 * Class LocalizationSync
 *
 * @author imbrish
 *
 * @since 29/09/2016
 * @see https://gist.github.com/imbrish/204e3b85cadfd8d6db0369d6469eb814
 */
class LocalizationSync extends Command
{
    private const LANG_HODOR = 'ho-HO';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:sync {base : Base language} {target : Target language}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize all lang files in target language with base language';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseLang   = $this->argument('base');
        $targetLang = $this->argument('target');

        $langDir   = lang_path();
        $baseDir   = $langDir . DIRECTORY_SEPARATOR . $baseLang;
        $targetDir = $langDir . DIRECTORY_SEPARATOR . $targetLang;

        $this->scanDir($baseLang, $targetLang, $baseDir, $targetDir);

        return 0;
    }

    public function scanDir(string $baseLang, string $targetLang, string $baseDir, string $targetDir): void
    {
        if (!file_exists($baseDir)) {
            $this->error(sprintf('Unable to find base dir %s', $baseDir));

            return;
        }

        if (!file_exists($targetDir)) {
            $this->error(sprintf('Unable to find target dir %s', $targetDir));

            return;
        }

        foreach (scandir($baseDir) as $name) {
            if ($name == '.' || $name == '..' || preg_match('#\.[0-9]{8}_[0-9]{6}\.php$#', $name)) {
                continue;
            }

            $basePath   = $baseDir . DIRECTORY_SEPARATOR . $name;
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $name;

            // Ensure dirs are created if we found one
            if (is_dir($basePath)) {
                $this->scanDir($baseLang, $targetLang, $basePath, $targetPath);

                // Don't parse the dir as a file so stop here
                continue;
            }

            if (file_exists($targetPath)) {
                $target = file_get_contents($targetPath);
                $lemmas = $this->parse($targetLang, $target);

                if ($lemmas === false) {
                    $this->error(sprintf('Parsing failed while reading \'%s/%s\'', $targetLang, $name));

                    continue;
                }
            } else {
                $target = false;
                $lemmas = [];
            }

            $base   = file_get_contents($basePath);
            $result = $this->parse($targetLang, $base, $lemmas);

            if ($result === false) {
                $this->error(sprintf('Parsing failed while syncing \'%s/%s\'', $baseLang, $name));

                continue;
            }

            $shortTargetPath = str_replace(base_path(), '', $targetPath);
            if ($target === false || $target != $result) {
                file_put_contents($targetPath, $result);

                $this->info(sprintf('File \'%s\' synchronized', $shortTargetPath));
            } else {
                $this->line(sprintf('File \'%s\' already in sync', $shortTargetPath));
            }
        }
    }

    /**
     * If lemmas are given substitute them in the content
     * Otherwise extract lemmas from content
     *
     * @param false|array $lemmas
     */
    public function parse(string $targetLang, string $content, mixed $lemmas = false): mixed
    {
        $result      = $lemmas === false ? [] : '';
        $tree        = [null];
        $expects_key = false;

        while (strlen($content) > 0) {
            // whitespace, multiline comment, single line comment, initial return statement, final semicolon, array pair separator, array item separator, php opening tag, php closing tag
            if (preg_match('#^\s+#', $content, $match)
                || preg_match('#^/\*.*?\*/#s', $content, $match)
                || preg_match('#^//.*?(?:\r\n?|\n)#', $content, $match)
                || preg_match('#^return#', $content, $match)
                || preg_match('#^;#', $content, $match)
                || preg_match('#^=>#', $content, $match)
                || preg_match('#^,#', $content, $match)
                || preg_match('#^<\?php#', $content, $match)
                || preg_match('#^\?>#', $content, $match)
            ) {
                $segment = $match[0];
            } // array opening
            else if (preg_match('#^\[#', $content, $match)) {
                if ($expects_key) {
                    return false;
                }

                $segment = $match[0];

                $expects_key = true;
            } // array closing
            else if (preg_match('#^]#', $content, $match)) {
                // there are no more open array, including top level
                if (count($tree) < 1) {
                    return false;
                }

                $segment = $match[0];

                array_pop($tree);
            } // single or double quoted string
            else if (preg_match('#^(")((?:[^"\\\\]|\\\\.)*)"#', $content, $match)
                || preg_match("#^(')((?:[^'\\\\]|\\\\.)*)'#", $content, $match)
            ) {
                if ($expects_key) {
                    $segment = $match[0];

                    // add key to tree structure
                    $tree[]      = $match[2];
                    $expects_key = false;
                } else {
                    // there is no key to assign the value to
                    if (count($tree) < 2) {
                        return false;
                    }

                    $key = implode('.', $tree);

                    // add value to results array
                    if ($lemmas === false) {
                        $result[$key] = $match[2];
                    } // replace value with matching, non-empty lemma
                    else if (array_key_exists($key, $lemmas) && strlen((string)$lemmas[$key]) > 0) {
                        $segment = $match[1] . $lemmas[$key] . $match[1];
                    } // mark value as not specified
                    else {
                        if ($targetLang === self::LANG_HODOR) {
                            $segment = $match[1] . 'Hodor' . $match[1];
                        } else {
                            $segment = $match[1] .
                                /*'@todo ' . $targetLang . ': ' . $key .*/
                                $match[1];
                        }
                    }

                    array_pop($tree);
                    $expects_key = true;
                }
            } // non recognized - something gone wrong
            else {
                return false;
            }

            $content = substr($content, strlen($match[0]));

            if ($lemmas !== false) {
                $result .= $segment;
            }
        }

        return $result;
    }
}
