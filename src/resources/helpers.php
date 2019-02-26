<?php

/**
 * Retrieve a repository for given key
 * Response can not store on cache
 * @param $key
 * @return mixed
 */
function repository($key)
{
    return app()
        ->make(\Hsntngr\Repository\IRepositoryManager::class)
        ->{$key};
}