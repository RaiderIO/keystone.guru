<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * The backfill carries its own copy of UserSlugService::generateBaseSlug() so this migration keeps
     * producing the same slugs when the service changes later. Users are visited in id order, so on a
     * collision the older account keeps the plain slug and the newer one gets the -2, -3, ... suffix.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug', 64)->nullable()->after('name');

            $table->unique('slug');
        });

        DB::table('users')
            ->select(['id', 'name'])
            ->whereNull('slug')
            ->chunkById(1000, function ($users) {
                foreach ($users as $user) {
                    $this->assignSlug($user->id, $this->generateBaseSlug((string)$user->name));
                }
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

    private function assignSlug(int $userId, string $baseSlug): void
    {
        $slug = $baseSlug;
        for ($suffix = 2; ; $suffix++) {
            try {
                DB::table('users')->where('id', $userId)->update(['slug' => $slug]);

                return;
            } catch (UniqueConstraintViolationException) {
                $slug = sprintf('%s-%d', $baseSlug, $suffix);
            }
        }
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
