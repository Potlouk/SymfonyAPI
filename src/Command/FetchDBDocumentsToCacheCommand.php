<?php
namespace App\Command;

use App\Interface\CacheStorageInterface;
use App\Repository\DocumentRepository;
use App\Repository\SettingRepository;
use App\Transformer\DocumentTransformer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:fetchDocumentsToCache',
    description: 'Fetches documents from db to cacheStorage',
    aliases: ['app:fetchDocumentsToCache'],
    hidden: false
)]
class FetchDBDocumentsToCacheCommand extends Command
{
    public function __construct(
        private readonly DocumentRepository    $documentRepository,
        private readonly SettingRepository     $settingRepository,
        private readonly CacheStorageInterface $cache,
        private readonly DocumentTransformer   $transformer)
    { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = 20;
        $offset = 0;

        do {
            $documents = $this->documentRepository->findBy([], null, $batchSize, $offset);
            
            foreach ($documents as $document) 
            $this->cache->save($document,  $this->transformer->transform($document, $this->settingRepository->findById(1)));
            
            $offset += $batchSize;
        } while (count($documents) === $batchSize);

        $output->writeln('Documents have been successfully fetched and cached.');

        return Command::SUCCESS;
    }
}