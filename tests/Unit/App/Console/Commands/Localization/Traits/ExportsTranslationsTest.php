<?php

namespace Tests\Unit\App\Console\Commands\Localization\Traits;

use App\Console\Commands\Localization\Traits\ExportsTranslations;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

#[Group('Console')]
#[Group('Localization')]
final class ExportsTranslationsTest extends TestCase
{
    private const TEST_LOCALE = 'zz_TEST_exportstranslations';

    #[Test]
    public function exportTranslations_givenAnyData_writesPhpCsFixerCompliantFile(): void
    {
        // Arrange
        $filePath = $this->prepareTestLocale();

        try {
            // Act
            $this->runExport([123 => 'Some NPC']);

            // Assert - php-cs-fixer requires no trailing whitespace on the opening tag line, and a
            // single trailing newline at end of file. Getting either wrong turns master red on every
            // export run, since all exported translation files are committed (see #3988).
            $contents = file_get_contents($filePath);

            $this->assertStringStartsWith('<?php' . PHP_EOL, $contents);
            $this->assertStringNotContainsString('<?php ', $contents);
            $this->assertStringEndsWith(';' . PHP_EOL, $contents);
            $this->assertStringEndsNotWith(PHP_EOL . PHP_EOL, $contents);
        } finally {
            $this->cleanUpTestLocale();
        }
    }

    #[Test]
    public function exportTranslations_givenExportedFile_producesLoadableTranslations(): void
    {
        // Arrange
        $filePath = $this->prepareTestLocale();
        $data     = [
            123 => 'Some NPC',
            456 => "Quoted ' and \\ escaped",
        ];

        try {
            // Act
            $this->runExport($data);

            // Assert - the written file must remain valid PHP returning exactly what went in
            $this->assertEquals($data, include $filePath);
        } finally {
            $this->cleanUpTestLocale();
        }
    }

    /**
     * @return string the path the export will be written to
     */
    private function prepareTestLocale(): string
    {
        new Filesystem()->ensureDirectoryExists(lang_path(self::TEST_LOCALE));

        return lang_path(sprintf('%s/npcs.php', self::TEST_LOCALE));
    }

    /**
     * Runs exportTranslations() through a throwaway command, since the trait relies on the Command
     * mixin for its info()/error() output.
     *
     * @param array<int|string, mixed> $data
     */
    private function runExport(array $data): void
    {
        $command = new class extends Command {
            use ExportsTranslations;

            protected $signature = 'test:exporttranslations';

            public string $locale = '';

            /** @var array<int|string, mixed> */
            public array $data = [];

            public function handle(): int
            {
                return $this->exportTranslations($this->locale, 'npcs.php', $this->data) ? 0 : 1;
            }
        };

        $command->locale = self::TEST_LOCALE;
        $command->data   = $data;
        $command->setLaravel($this->app);

        $this->assertEquals(0, $command->run(new ArrayInput([]), new NullOutput()));
    }

    private function cleanUpTestLocale(): void
    {
        new Filesystem()->deleteDirectory(lang_path(self::TEST_LOCALE));
    }
}
