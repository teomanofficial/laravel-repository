<?php

namespace Hsntngr\Repository\Commands;

use Illuminate\Console\Command;

class MakeRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {model} {--key=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a repository for model';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    /**
     * Model name without namespace
     * @var string
     */
    protected $model;

    /**
     * Repository alias
     * @var string
     */
    protected $key;

    const BACKSLASH = "\\";

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handle with command
     * Generates repository for given model
     */
    public function handle()
    {
        $this->model = $this->argument("model");

        if (count($this->option("key"))) {
            $this->key = $this->option("key")[0];
        }

        if (!$this->repositoryDirExists())
            $this->createRepositoryDir();

        if (!$this->baseRepositoryPublished())
            $this->publishBaseRepository();

        if (!$this->modelExists()) {
            $this->error($this->model . " model not found in " . config("repository.model.path") . " folder");
            return;
        };

        if ($this->repositoryExists()) {
            $this->error($this->model . "Repository already exists");
            return;
        };

        $repository = $this->makeRepository();

        $this->write($repository);

        $this->updateConfig();

        $this->line($this->model . "Repository  created successfully");

        $this->call("config:cache");
    }

    /**
     * Check repository directory exists
     * @return bool
     */
    protected function repositoryDirExists(): bool
    {
        return is_dir(base_path(config("repository.path")));
    }

    /**
     * Create repository directory
     */
    protected function createRepositoryDir(): void
    {
        mkdir(base_path(config("repository.path")));
        $directory = trim(config("repository.path"), DIRECTORY_SEPARATOR);
        $this->line($directory . " directory created successfully");
    }

    /**
     * Check given model exists
     * @return bool
     */
    protected function modelExists(): bool
    {
        if ($handle = opendir(base_path(config("repository.model.path")))) {
            $models = [];
            while (($model = readdir($handle)) !== false) {
                if ($model != "." && $model != "..")
                    array_push($models, $model);
            }
            closedir($handle);
            return in_array($this->model . ".php", $models);
        } else {
            $directory = trim(config("repository.model.path"), DIRECTORY_SEPARATOR);
            $this->error($directory . " directory not found");
        }
    }

    /**
     * Check BaseRepository published
     * @return bool
     */
    protected function baseRepositoryPublished(): bool
    {
        $handle = opendir(base_path(config("repository.path")));
        $repositories = [];
        while (($repository = readdir($handle)) !== false) {
            if ($repository != "." && $repository != "..")
                array_push($repositories, $repository);
        }
        closedir($handle);

        return in_array("BaseRepository.php", $repositories);
    }

    /**
     * Publish BaseRepository if not published before
     */
    protected function publishBaseRepository(): void
    {
        $plainBaseRepositoryContent = file_get_contents(__DIR__ . "/../resources/base_repository");

        $baseRepositoryContent = str_replace([
            ":namespace",
        ], [
            trim(config("repository.namespace"), self::BACKSLASH),
        ], $plainBaseRepositoryContent);
        $filepath = base_path(config("repository.path")) . DIRECTORY_SEPARATOR . "BaseRepository.php";
        file_put_contents($filepath, $baseRepositoryContent);
        $this->line("BaseRepository created successfully");
    }

    /**
     * Check repository exists for given model
     * @return bool
     */
    protected function repositoryExists(): bool
    {
        return in_array($this->model . "Repository", config("repository.map"));
    }

    /**
     * Create repository for given model
     * @return string
     */
    protected function makeRepository(): string
    {
        $plainRepositoryContent = file_get_contents(__DIR__ . "/../resources/repository");

        return str_replace([
            ":namespace",
            ":modelClass",
            ":modelName",
            ":modelLower"
        ], [
            trim(config("repository.namespace"), self::BACKSLASH),
            trim(config("repository.model.namespace"), self::BACKSLASH) . self::BACKSLASH . $this->model,
            $this->model,
            strtolower($this->model)
        ], $plainRepositoryContent);
    }

    /**
     * Write repository to given path (config/repository.php path)
     * @param $repository
     */
    protected function write($repository): void
    {
        $path = base_path(config("repository.path"));
        $filepath = $path . DIRECTORY_SEPARATOR . $this->model . "Repository.php";

        file_put_contents($filepath, $repository);
    }

    /**
     * Update config file
     * Add repository with its alias
     */
    protected function updateConfig(): void
    {
        config("repository.map")[$this->key ?? strtolower($this->model)] = $this->model . "Repository";

        $path = config_path("repository.php");

        $dump = var_export(config("repository"), true);
        $dump = preg_replace('#(?:\A|\n)([ ]*)array \(#i', '[', $dump);
        $dump = preg_replace('#\n([ ]*)\),#', "\n$1],", $dump);
        $dump = preg_replace('#\n\)#', "\n];", $dump);
        $dump = preg_replace('#=> \[\n\s+\],\n#', "=> [],\n", $dump);

        $content = "<?php \n\n return " . $dump;

        file_put_contents($path, $content);
    }

}