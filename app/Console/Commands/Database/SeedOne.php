<?php

namespace App\Console\Commands\Database;

use App\Service\Cache\CacheServiceInterface;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Throwable;

class SeedOne extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backs up the current database';

    /**
     * @var string
     */
    protected $signature = 'db:seedone {--database=} {className}';

    /**
     * The connection resolver instance.
     *
     * @var Resolver
     */
    protected $resolver;

    /**
     * Create a new database seed command instance.
     *
     * @param  Resolver $resolver
     * @return void
     */
    public function __construct(Resolver $resolver)
    {
        parent::__construct();

        $this->resolver = $resolver;
    }

    /**
     * Execute the console command.
     * @throws Throwable
     */
    public function handle(
        CacheServiceInterface $cacheService,
        DatabaseSeeder        $databaseSeeder,
    ): int {
        $className = $this->argument('className');

        $databaseSeeder->setCommand($this);

        $this->resolver->setDefaultConnection($this->getDatabase());

        try {
            $classNames = explode(',', $className);
            $this->info(sprintf('Seeding database with only %s...', implode(', ', $classNames)));

            $fullClassNames = collect($classNames)->map(function ($className) {
                return 'Database\\Seeders\\' . $className;
            })->toArray();

            $databaseSeeder->run($cacheService, $fullClassNames);
        } finally {
            $this->info('Seeding database OK!');
        }

        return 0;
    }

    /**
     * Get the name of the database connection to use.
     *
     * @return string
     */
    protected function getDatabase(): string
    {
        $database = $this->input->getOption('database');

        return $database ?: $this->laravel['config']['database.default'];
    }
}
