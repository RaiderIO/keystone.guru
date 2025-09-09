<?php

namespace App\Console\Commands\Generate;

use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class Repository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:repository {--model=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a new repository (or all of them if they don\'t exist yet)';


    public function handle(): int
    {
        $model = $this->option('model');

        $interfaceNamespace  = 'App\\Repositories\\Interfaces';
        $repositoryNamespace = 'App\\Repositories\\Database';
        $modelNamespace      = 'App\\Models';

        if ($model === null) {
            // Find all models
            $repositoryClasses = collect(ClassFinder::getClassesInNamespace($repositoryNamespace, ClassFinder::RECURSIVE_MODE));
            $modelClasses      = ClassFinder::getClassesInNamespace($modelNamespace, ClassFinder::RECURSIVE_MODE);

            foreach ($modelClasses as $modelClass) {
                $this->createRepositoryForClass(
                    $interfaceNamespace,
                    $repositoryNamespace,
                    $modelNamespace,
                    $modelClass
                );
            }

            dd($repositoryClasses);
        }

        dd($model);
    }

    public function createRepositoryForClass(
        string $interfaceNamespace,
        string $repositoryNamespace,
        string $modelNamespace,
        string $modelClass
    ): bool {
        // Ignore non-models
        if (!is_subclass_of($modelClass, Model::class)) {
            $this->warn(sprintf('Class %s is not a Model!', $modelClass));

            return false;
        }

        return $this->saveRepository(
                $interfaceNamespace,
                $repositoryNamespace,
                $modelNamespace,
                $modelClass
            ) && $this->saveInterface(
                $interfaceNamespace,
                $repositoryNamespace,
                $modelNamespace,
                $modelClass
            );
    }

    private function saveRepository(
        string $interfaceNamespace,
        string $repositoryNamespace,
        string $modelNamespace,
        string $modelClass
    ): bool {
        // Check if the repository already exists for this model
        $targetRepositoryFullClassName = sprintf('%s%sRepository',
            $repositoryNamespace,
            str_replace($modelNamespace, '', $modelClass)
        );

        if (class_exists($targetRepositoryFullClassName)) {
            $this->warn(sprintf('Class %s already exists!', $targetRepositoryFullClassName));

            return false;
        }

        $newRepositoryFilePath = app_path(
            sprintf('%s.php', str_replace([
                'App\\',
                '\\',
            ], [
                '',
                '/',
            ], $targetRepositoryFullClassName))
        );

        $this->ensureDirForFile($newRepositoryFilePath);
        $subNamespaces = $this->getSubNamespaces($modelClass, $modelNamespace);

        $result = file_put_contents(
            $newRepositoryFilePath,
            $this->getTemplate('Repository', [
                // App\\Repositories\\Database\\Sub
                ':namespace'          => sprintf('%s%s', $repositoryNamespace, $subNamespaces),
                ':modelFullClassName' => $modelClass,
                ':modelClassName'     => last(explode('\\', $modelClass)),
                ':interfaceNamespace' => sprintf('%s%s', $interfaceNamespace, $subNamespaces),
            ])
        );

        if ($result) {
            $this->info(sprintf('Generated %s', $newRepositoryFilePath));
        } else {
            $this->warn(sprintf('Unable to generate %s! Permission issues?', $newRepositoryFilePath));
        }

        return $result;
    }

    private function saveInterface(
        string $interfaceNamespace,
        string $repositoryNamespace,
        string $modelNamespace,
        string $modelClass
    ): bool {
        // Check if the repository already exists for this model
        $targetInterfaceClassName = sprintf('%s%sRepositoryInterface',
            $repositoryNamespace,
            str_replace($modelNamespace, '', $modelClass)
        );
        $newInterfaceFilePath     = app_path(
            sprintf('%s.php',
                str_replace([
                    $repositoryNamespace,
                    'App\\',
                    '\\',
                ], [
                    $interfaceNamespace,
                    '',
                    '/',
                ], $targetInterfaceClassName)
            )
        );

        $this->ensureDirForFile($newInterfaceFilePath);
        $subNamespaces = $this->getSubNamespaces($modelClass, $modelNamespace);

        $result = file_put_contents(
            $newInterfaceFilePath,
            $this->getTemplate('Interface', [
                ':namespace'          => sprintf('%s%s', $interfaceNamespace, $subNamespaces),
                ':modelFullClassName' => $modelClass,
                ':modelClassName'     => last(explode('\\', $modelClass)),
            ])
        );

        if ($result) {
            $this->info(sprintf('Generated %s', $newInterfaceFilePath));
        } else {
            $this->warn(sprintf('Unable to generate %s! Permission issues?', $newInterfaceFilePath));
        }

        return $result;
    }

    private function ensureDirForFile(string $filePath): bool
    {
        $result = true;

        $newInterfaceDir = dirname($filePath);
        if (!file_exists($newInterfaceDir)) {
            $this->info(sprintf('Creating new dir %s', $newInterfaceDir));
            $result = mkdir($newInterfaceDir, 0775, true);
        }

        return $result;
    }

    public function getSubNamespaces(string $modelClass, string $modelNamespace): string
    {
        // Ensure we have the correct namespace by taking into account all subfolders
        // App\Models\Sub\MyClass
        $modelClassParts = explode('\\', $modelClass);
        // App, Models, Sub, MyClass
        array_pop($modelClassParts);
        // App, Models, Sub

        // // App\\Models\\Sub -> \\Sub
        return str_replace($modelNamespace, '', implode('\\', $modelClassParts));
    }

    private function getTemplate(string $template, array $replace): string
    {
        $template = file_get_contents(sprintf('%s/Templates/%s.txt', __DIR__, $template));

        return str_replace(array_keys($replace), array_values($replace), $template);
    }
}
