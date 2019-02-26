<?php

namespace Hsntngr\Repository;

use Hsntngr\Repository\Exceptions\RepositoryNotFound;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RepositoryManager implements IRepositoryManager
{
    /**
     * Cache options
     * @var array
     */
    private $cache = [
        "enable" => false,
        "key" => "",
        "minutes" => null
    ];

    const BACKSLASH = "\\";

    /**
     * Instantiate repository for given key
     * Responses can not store on cache
     * @param $rKey
     * @return mixed
     * @throws RepositoryNotFound
     */
    public function __get($rKey)
    {
        if ($rKey == "db") {
            return DB::getFacadeRoot();
        }

        if (!$this->repositoryExist($rKey)) {
            throw new RepositoryNotFound("Repository not found for " . $rKey);
        }

        return $this->getRepository($rKey);
    }

    /**
     * Instantiate repository and provide to cacheable process
     * @param $rKey (repositoryKey)
     * @param callable $callback
     * @return mixed
     * @throws RepositoryNotFound
     */
    public function repository($rKey, callable $callback)
    {
        if (is_array($rKey)) {
            $key = $rKey[0];
            $modelInstance = $rKey[1];
        } else {
            $key = $rKey;
            $modelInstance = "";
        }

        if (!$this->repositoryExist($key))
            throw new RepositoryNotFound("Repository not found for " . $key);

        if ($this->cache["enable"] && Cache::has($this->cache["key"])) {
            $cacheKey = $this->cache["key"];
            $this->resetCache();
            return Cache::get($cacheKey);
        }

        $repository = $this->getRepository($key, $modelInstance);

        $response = call_user_func($callback, $repository);

        if ($this->cache["enable"]) $this->storeOnCache($response);

        return $response;
    }

    /**
     * Check repository exists for given key
     * @param string $repositoryKey
     * @return bool
     */
    private function repositoryExist(string $repositoryKey): bool
    {
        return array_key_exists($repositoryKey, config("repository.map"));
    }

    /**
     * Instantiate a repository for given key
     * @param $repositoryKey
     * @param null $modelInstance
     * @return mixed
     */
    private function getRepository($repositoryKey, $modelInstance = null)
    {
        $namespace = trim(config("repository.namespace"), self::BACKSLASH);
        $repository = $namespace . self::BACKSLASH . config("repository.map")[$repositoryKey];

        $modelInstance
            ? app()->bind($repositoryKey, function () use ($repository, $modelInstance) {
            return new $repository($modelInstance);
        })
            : app()->bind($repositoryKey, $repository);

        return app()->make($repositoryKey);
    }

    /**
     * Retrieve Query Builder for specific table
     * @param string $table
     * @return \Illuminate\Database\Query\Builder
     */
    public function table(string $table): \Illuminate\Database\Query\Builder
    {
        return DB::table($table);
    }

    /**
     * Enable cache and set cache options
     * @param string $key
     * @param int|null $minutes
     * @return self
     */
    public function cache(string $key, int $minutes = null): self
    {
        $this->cache = [
            "enable" => true,
            "key" => $key,
            "minutes" => $minutes
        ];
        return $this;
    }

    /**
     * Store repository response on cache
     * @param $data
     */
    private function storeOnCache($data): void
    {
        $this->cache["minutes"]
         ? Cache::put($this->cache["key"], $data, $this->cache["minutes"])
         : Cache::forever($this->cache["key"],$data);

        $this->resetCache();
    }

    /**
     * Set default cache options
     */
    private function resetCache(): void
    {
        $this->cache = [
            "enable" => false,
            "key" => "",
            "minutes" => null
        ];
    }
}