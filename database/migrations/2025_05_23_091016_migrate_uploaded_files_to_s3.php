<?php

use App\Models\File;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $consoleOutput = new ConsoleOutput();
        $diskName      = 's3_user_uploads';
        $disk          = Storage::disk($diskName);

        /** @var Collection<File> $files */
        $files = App\Models\File::query()
            ->whereIn('model_class', ['App\\Models\\User', 'App\\Models\\Team'])
            ->whereNotIn('disk', ['s3', 's3_user_uploads'])
            ->get();

        $consoleOutput->writeln(sprintf(' - Starting migration of %d files to %s', $files->count(), $diskName));

        foreach ($files as $file) {
            try {
                $tmpPath = tempnam(sys_get_temp_dir(), 'file_');

                // Make sure the dimensions never exceed 256x256 but maintain their aspect ratio
                (new ImageManager(new ImagickDriver()))
                    ->read(Storage::disk($file->disk)->path($file->path))
                    ->scaleDown(256, 256)
                    ->save($tmpPath, 90);

                $disk->put($file->path, file_get_contents($tmpPath));

                $file->update([
                    'disk' => $diskName,
                ]);

                if (!unlink($tmpPath)) {
                    $consoleOutput->writeln(sprintf(' - File %d: Failed to delete local temp file %s', $file->id, $tmpPath));
                } else {
                    $consoleOutput->writeln(sprintf(' - File %d: Migrated file to S3', $file->id));
                }
            } catch (Exception $e) {
                // Log the error
                $consoleOutput->writeln(sprintf(' - File %d: Failed to migrate file %s to S3 -> %s', $file->id, $file->path, $e->getMessage()));
            }
        }

        $consoleOutput->writeln(sprintf('Finished migration of %d files to %s', $files->count(), $diskName));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
