<?php
namespace App\EventListener;

use App\Interface\CacheStorageInterface;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
readonly class CacheCleanerListener
{
    public function __construct(
        private CacheStorageInterface $cache
    ) {}

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->cache->delete($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->cache->delete($args->getObject());
    }
}