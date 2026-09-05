<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards against code that only works because a connection is non-strict.
 *
 * `mysql` and both phpunit connections set `strict => false`, but `migrate` sets it to true - and
 * `db:seed --database=migrate` is how CI and every fresh machine create their users. An insert that
 * omits a NOT NULL column with no default therefore passes every local test and dies during
 * seeding with "Field '<column>' doesn't have a default value" (#4498).
 *
 * Nothing else in the suite exercises the strict path, so these tests turn the phpunit connection
 * strict for their duration rather than writing to DB_DATABASE, which is the very isolation #4346
 * exists to protect.
 */
#[Group('Database')]
final class StrictConnectionUserInsertTest extends PublicTestCase
{
    private const string CONNECTION = 'phpunit';

    /**
     * Runs $callback with the phpunit connection in strict mode, inside a transaction that is
     * always rolled back. Asserts strict actually took effect first - without that check every
     * assertion below would pass vacuously on a non-strict connection.
     */
    private function runInStrictTransaction(callable $callback): void
    {
        $originalStrict = config(sprintf('database.connections.%s.strict', self::CONNECTION));

        config([sprintf('database.connections.%s.strict', self::CONNECTION) => true]);
        DB::purge(self::CONNECTION);

        try {
            $sqlMode = DB::connection(self::CONNECTION)->select('SELECT @@session.sql_mode AS mode')[0]->mode;
            $this->assertStringContainsString(
                'STRICT_TRANS_TABLES',
                $sqlMode,
                'The connection is not actually strict, so this test would pass without proving anything.',
            );

            DB::connection(self::CONNECTION)->beginTransaction();

            try {
                $callback();
            } finally {
                DB::connection(self::CONNECTION)->rollBack();
            }
        } finally {
            config([sprintf('database.connections.%s.strict', self::CONNECTION) => $originalStrict]);
            DB::purge(self::CONNECTION);
        }
    }

    #[Test]
    public function create_givenStrictConnectionAndSeederAttributes_insertsTheUser(): void
    {
        // Arrange & Act & Assert
        $this->runInStrictTransaction(function (): void {
            // Exactly the attribute set LaratrustSeeder writes - the insert that broke CI
            $user = User::create([
                'name'         => 'Strict Mode Probe',
                'public_key'   => User::generateRandomPublicKey(),
                'echo_color'   => randomHexColor(),
                'email'        => 'strict_mode_probe@app.com',
                'password'     => Hash::make('password'),
                'legal_agreed' => 1,
            ]);

            $this->assertTrue($user->exists);
        });
    }

    #[Test]
    public function create_givenStrictConnectionAndFactory_insertsTheUser(): void
    {
        // Arrange & Act & Assert
        $this->runInStrictTransaction(function (): void {
            $user = User::factory()->create();

            $this->assertTrue($user->exists);
        });
    }
}
