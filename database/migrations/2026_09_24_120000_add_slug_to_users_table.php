<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** @var array<string, string> Collation key per slug */
    private array $collationKeys = [];

    private string $slugCollation;

    /**
     * Run the migrations.
     *
     * The backfill carries its own copy of UserSlugService::generateBaseSlug() so this migration keeps
     * producing the same slugs when the service changes later. Users are visited in id order, so on a
     * collision the older account keeps the plain slug and the newer one gets the -2, -3, ... suffix.
     *
     * Slugs are resolved in memory, written back with one update per chunk and indexed afterwards: one
     * update per user took over 8 minutes on a production-sized user table. Taken slugs are keyed by
     * their MySQL collation weight, since the unique index treats e.g. `café` and `cafe` as equal.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug', 64)->nullable()->after('name');
        });

        $this->slugCollation = collect(Schema::getColumns('users'))->firstWhere('name', 'slug')['collation'];

        /** @var array<string, true> $takenCollationKeys */
        $takenCollationKeys = [];
        /** @var array<string, int> $nextSuffixByBaseSlug */
        $nextSuffixByBaseSlug = [];

        DB::table('users')
            ->select(['id', 'name'])
            ->chunkById(1000, function (Collection $users) use (&$takenCollationKeys, &$nextSuffixByBaseSlug) {
                /** @var array<int, string> $baseSlugByUserId */
                $baseSlugByUserId = [];
                foreach ($users as $user) {
                    $baseSlugByUserId[(int)$user->id] = $this->generateBaseSlug((string)$user->name);
                }

                $this->loadCollationKeys($baseSlugByUserId);

                /** @var array<int, string> $slugByUserId */
                $slugByUserId = [];
                foreach ($baseSlugByUserId as $userId => $baseSlug) {
                    // Suffixes are keyed on the collation key so `Café` and `cafe` share one -2, -3, ... sequence
                    $baseCollationKey = $this->collationKeys[$baseSlug];

                    $slug = $baseSlug;
                    if (isset($takenCollationKeys[$baseCollationKey])) {
                        $suffix = $nextSuffixByBaseSlug[$baseCollationKey] ?? 2;
                        while (isset($takenCollationKeys[$this->collationKey($slug = sprintf('%s-%d', $baseSlug, $suffix))])) {
                            $suffix++;
                        }

                        $nextSuffixByBaseSlug[$baseCollationKey] = $suffix + 1;
                    }

                    $takenCollationKeys[$this->collationKey($slug)] = true;
                    $slugByUserId[$userId]                          = $slug;
                }

                $this->writeSlugs($slugByUserId);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }

    /**
     * @param array<array-key, string> $slugs
     */
    private function loadCollationKeys(array $slugs): void
    {
        $missingSlugs = array_values(array_unique(array_filter(
            $slugs,
            fn(string $slug) => !isset($this->collationKeys[$slug]),
        )));
        if ($missingSlugs === []) {
            return;
        }

        $expression = sprintf('HEX(WEIGHT_STRING(? COLLATE %s))', $this->slugCollation);
        $columns    = [];
        foreach (array_keys($missingSlugs) as $index) {
            $columns[] = sprintf('%s AS k%d', $expression, $index);
        }

        $row = (array)DB::selectOne(sprintf('SELECT %s', implode(', ', $columns)), $missingSlugs);
        foreach ($missingSlugs as $index => $slug) {
            $this->collationKeys[$slug] = $row[sprintf('k%d', $index)];
        }
    }

    private function collationKey(string $slug): string
    {
        $this->loadCollationKeys([$slug]);

        return $this->collationKeys[$slug];
    }

    /**
     * @param array<int, string> $slugByUserId
     */
    private function writeSlugs(array $slugByUserId): void
    {
        if ($slugByUserId === []) {
            return;
        }

        $cases    = [];
        $bindings = [];
        foreach ($slugByUserId as $userId => $slug) {
            $cases[]    = 'WHEN ? THEN ?';
            $bindings[] = $userId;
            $bindings[] = $slug;
        }

        DB::update(
            sprintf(
                'UPDATE users SET slug = CASE id %s END WHERE id IN (%s)',
                implode(' ', $cases),
                implode(', ', array_fill(0, count($slugByUserId), '?')),
            ),
            [...$bindings, ...array_keys($slugByUserId)],
        );
    }

    private function generateBaseSlug(string $name): string
    {
        $normalizedName = Normalizer::normalize($name, Normalizer::FORM_C);
        if ($normalizedName === false) {
            return 'user';
        }

        $slug = preg_replace('/[^\pL\pM\pN_]+/u', '-', mb_strtolower($normalizedName));
        if ($slug === null) {
            return 'user';
        }

        $slug = trim(mb_substr(trim($slug, '-'), 0, 48), '-');

        return $slug === '' ? 'user' : $slug;
    }
};
