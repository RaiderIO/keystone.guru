<?php

use Illuminate\Database\Migrations\Migration;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

/**
 * Personal access tokens can only be issued once a personal access client exists, and Passport only creates one
 * through an interactive artisan command - so it arrives through a migration to exist on staging/production too.
 * Idempotent: an existing personal access client is left alone.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = Passport::client()->newQuery()
            ->where('revoked', false)
            ->get()
            ->contains(static fn($client): bool => $client->hasGrantType('personal_access'));

        if (!$exists) {
            app(ClientRepository::class)->createPersonalAccessGrantClient('Keystone.guru Personal Access Client', 'users');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Passport::client()->newQuery()
            ->where('name', 'Keystone.guru Personal Access Client')
            ->delete();
    }
};
