<?php

namespace Tests\Feature\App\Models;

use App\Models\File;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToDeleteFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('File')]
final class FileTest extends PublicTestCase
{
    private function createFile(string $disk, string $path): File
    {
        return File::create([
            'model_id'    => 1,
            'model_class' => self::class,
            'disk'        => $disk,
            'path'        => $path,
        ]);
    }

    #[Test]
    public function deleteFromDisk_givenLocalEnvironmentAndRemoteDisk_leavesTheDiskObjectUntouched(): void
    {
        // Arrange - a local environment restoring a production database backup can have File rows
        // that legitimately still point at a real S3 disk; those objects must stay read-only.
        $originalEnv      = $this->app['env'];
        $this->app['env'] = 'local';
        Storage::fake('s3_user_uploads');
        Storage::disk('s3_user_uploads')->put('some/path.jpg', 'prod-backup-bytes');

        $file = $this->createFile('s3_user_uploads', 'some/path.jpg');

        try {
            // Act
            $result = $file->deleteFromDisk();

            // Assert
            $this->assertTrue($result);
            Storage::disk('s3_user_uploads')->assertExists('some/path.jpg');
        } finally {
            $this->app['env'] = $originalEnv;
            $file->delete();
        }
    }

    #[Test]
    public function deleteFromDisk_givenLocalEnvironmentAndLocalDisk_deletesTheDiskObject(): void
    {
        // Arrange - the guard only protects remote disks; local-driver disks are never a real
        // production backup and must keep deleting as normal.
        $originalEnv      = $this->app['env'];
        $this->app['env'] = 'local';
        Storage::fake('public');
        Storage::disk('public')->put('some/path.jpg', 'bytes');

        $file = $this->createFile('public', 'some/path.jpg');

        try {
            // Act
            $result = $file->deleteFromDisk();

            // Assert
            $this->assertTrue($result);
            Storage::disk('public')->assertMissing('some/path.jpg');
        } finally {
            $this->app['env'] = $originalEnv;
            $file->delete();
        }
    }

    #[Test]
    public function deleteFromDisk_givenProductionEnvironmentAndRemoteDisk_deletesTheDiskObject(): void
    {
        // Arrange - the guard only protects local environments; production must keep deleting for real.
        $originalEnv      = $this->app['env'];
        $this->app['env'] = 'production';
        Storage::fake('s3_user_uploads');
        Storage::disk('s3_user_uploads')->put('some/path.jpg', 'bytes');

        $file = $this->createFile('s3_user_uploads', 'some/path.jpg');

        try {
            // Act
            $result = $file->deleteFromDisk();

            // Assert
            $this->assertTrue($result);
            Storage::disk('s3_user_uploads')->assertMissing('some/path.jpg');
        } finally {
            $this->app['env'] = $originalEnv;
            $file->delete();
        }
    }

    #[Test]
    public function deleteFromDisk_givenADiskThatThrowsOnDelete_reportsTheExceptionAndReturnsFalse(): void
    {
        // Arrange - an S3 disk is configured to throw, e.g. on a 403 from DeleteObject
        Exceptions::fake();
        $this->useDiskThatFailsToDelete('s3_user_uploads');

        $file = $this->createFile('s3_user_uploads', 'some/path.jpg');

        try {
            // Act
            $result = $file->deleteFromDisk();

            // Assert
            $this->assertFalse($result);
            Exceptions::assertReported(UnableToDeleteFile::class);
        } finally {
            File::query()->whereKey($file->id)->delete();
        }
    }

    #[Test]
    public function delete_givenADiskThatThrowsOnDelete_stillDeletesTheRow(): void
    {
        // Arrange
        Exceptions::fake();
        $this->useDiskThatFailsToDelete('s3_user_uploads');

        $file = $this->createFile('s3_user_uploads', 'some/path.jpg');

        try {
            // Act
            $result = $file->delete();

            // Assert
            $this->assertTrue($result);
            $this->assertDatabaseMissing('files', ['id' => $file->id]);
        } finally {
            File::query()->whereKey($file->id)->delete();
        }
    }

    private function useDiskThatFailsToDelete(string $disk): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('delete')
            ->willThrowException(UnableToDeleteFile::atLocation('some/path.jpg', 'Access Denied'));

        Storage::set($disk, $filesystem);
    }
}
