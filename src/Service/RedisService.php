<?php

namespace App\Service;

use App\Interface\CacheStorageInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class RedisService implements CacheStorageInterface {
    private RedisAdapter $storage;

    public function __construct(
    ) {
        $connection = RedisAdapter::createConnection('redis:?host[redis:6379]&dbindex=0');
        $this->storage = new RedisAdapter($connection);
    }

    /**
     * Saves data to Redis cache
     *
     * Stores the provided data in Redis with the key generated from the object
     * or using the provided key directly. Sets an expiration of 12 hours.
     *
     * @param mixed $object The object to cache or a string key
     * @param array<string, mixed> $data The data to cache
     * @return void
     * @throws InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function save(mixed $object, array $data): void {
        if (is_object($object))
        $key = $this->getKeyFrom($object);
        else $key = $object;

        $row = $this->storage->getItem($key);
        $row->set($data);
        $row->expiresAfter(43200);
        $this->storage->save($row);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function deleteKey(string $key): void {
        $this->storage->deleteItem($key);
    }

    /**
     * Deletes an object from Redis cache
     *
     * Removes the cached data for the specified object by generating a key
     * from the object's class and ID.
     *
     * @param object $object The object to remove from cache
     * @return void
     * @throws InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function delete(object $object): void {
        $key = $this->getKeyFrom($object);
        $this->storage->deleteItem($key);
    }

    /**
     * Retrieves data from Redis cache
     *
     * Gets cached data using a key generated from the object's class and ID
     * or using the provided key directly.
     *
     * @param mixed $object The object to retrieve or a string key
     * @param string $id The ID to append to the key
     * @return array<string, mixed>|null The cached data or null if not found
     * @throws InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function get(mixed $object, string $id): ?array {
        if (is_object($object))
        $key = get_class($object);
        else $key = $object;

        $result = $this->storage->getItem("{$key}{$id}");
        if (!$result->isHit())
        return null;

        return $result->get();
    }
    
    /**
     * Generates a cache key from an object
     * 
     * Creates a key using the object's class name and its UUID or ID.
     * 
     * @param object $object The object to generate a key for
     * @return string The generated key
     */
    private function getKeyFrom(object $object) : string {
        $key = str_replace('\\','',get_class($object));
        
        if (method_exists($object,'getUuid'))
        $id = $object->getUuid();
        else $id = $object->getId();

        return "{$key}{$id}";
    }

    /**
     * Flushes storage
     * @return bool
     */
    public function flush() : bool {
        return $this->storage->clear();
    }
}