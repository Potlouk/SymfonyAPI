<?php
namespace App\Command;

use App\Interface\CacheStorageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:redisFlush',
    description: 'Flushes cacheStorage',
    aliases: ['app:redisFlush'],
    hidden: false
)]
class RedisFlushCommand extends Command
{
    public function __construct(
        private readonly CacheStorageInterface $cache,
    ){ parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->cache->flush())
            $output->writeln('Redis flush successful.');
        else
            $output->writeln('Redis flush failed.');

        return Command::SUCCESS;
    }
}