<?php
namespace App\Command;

use App\Interface\CacheStorageInterface;
use App\Repository\PropertyRepository;
use App\Transformer\PropertyTransformer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:fetchPropertiesToCache',
    description: 'Fetches properties from db to cacheStorage',
    aliases: ['app:fetchPropertiesToCache'],
    hidden: false
)]
class FetchDBPropertiesToCacheCommand extends Command
{
    public function __construct(
        private readonly PropertyRepository    $propertyRepository,
        private readonly CacheStorageInterface $cache,
        private readonly PropertyTransformer   $transformer)
    { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = 20;
        $offset = 0;

        do {
            $properties = $this->propertyRepository->findBy([], null, $batchSize, $offset);
            
            foreach ($properties as $property) 
            $this->cache->save($property,  $this->transformer->transform($property));
            
            $offset += $batchSize;
        } while (count($properties) === $batchSize);

        $output->writeln('Properties have been successfully fetched and cached.');

        return Command::SUCCESS;
    }
}