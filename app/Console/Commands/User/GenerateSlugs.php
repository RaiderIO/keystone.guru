<?php

namespace App\Console\Commands\User;

use App\Models\User;
use App\Service\User\UserSlugServiceInterface;
use Illuminate\Console\Command;

class GenerateSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:generateslugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gives every user without a profile URL slug one. Users that already have a slug are left alone.';

    public function handle(UserSlugServiceInterface $userSlugService): int
    {
        $count = 0;

        foreach (User::query()->whereNull('slug')->lazyById() as $user) {
            /** @var User $user */
            $user->slug       = $userSlugService->findAvailableSlug($user->name, $user->id);
            $user->timestamps = false;
            $user->save();

            $count++;
        }

        $this->info(sprintf('Generated a slug for %d user(s)', $count));

        return self::SUCCESS;
    }
}
