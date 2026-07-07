<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Override;

class LoggingServiceProvider extends ServiceProvider
{
    /**
     * Logging directories occur at varying depths below app/, e.g. app/Jobs/Logging, app/Service/WowTools/Logging
     * and app/Service/CombatLog/Builders/Logging.
     */
    private const array LOGGING_DIRECTORY_DEPTHS = ['*', '*/*', '*/*/*', '*/*/*/*'];

    #[Override]
    public function register(): void
    {
        parent::register();

        // Every {Name}LoggingInterface is bound to its {Name}Logging implementation by convention - a new Logging
        // class needs no registration here as long as it follows the naming convention (see the structured-logging skill)
        foreach (self::getLoggingInterfaces() as $loggingInterface) {
            $this->app->bind($loggingInterface, str_replace('LoggingInterface', 'Logging', $loggingInterface));
        }
    }

    /** @return array<int, class-string> */
    public static function getLoggingInterfaces(): array
    {
        $result = [];

        foreach (self::LOGGING_DIRECTORY_DEPTHS as $depth) {
            foreach (glob(app_path(sprintf('%s/Logging/*LoggingInterface.php', $depth))) ?: [] as $filePath) {
                /** @var class-string $className */
                $className = sprintf(
                    'App\\%s',
                    str_replace('/', '\\', substr($filePath, strlen(app_path()) + 1, -strlen('.php'))),
                );

                $result[] = $className;
            }
        }

        return $result;
    }
}
