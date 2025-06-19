<?php 

namespace App\Interface;

interface CacheStorageInterface {
    public function save(mixed $object, array $data): void;
    public function delete(object $object): void;
    public function deleteKey(string $key): void;
    public function flush(): bool;
    public function get(mixed $object, string $id): ?array;
}