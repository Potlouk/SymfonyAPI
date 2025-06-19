<?php
namespace App\Command;

use App\Interface\CacheStorageInterface;
use App\Repository\ReportRepository;
use App\Repository\SettingRepository;
use App\Transformer\ReportTransformer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:fetchReportsToCache',
    description: 'Fetches reports from db to cacheStorage',
    aliases: ['app:fetchReportsToCache'],
    hidden: false
)]
class FetchDBReportsToCacheCommand extends Command
{
    public function __construct(
        private readonly ReportRepository      $reportRepository,
        private readonly SettingRepository     $settingRepository,
        private readonly CacheStorageInterface $cache,
        private readonly ReportTransformer     $transformer)
    { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = 20;
        $offset = 0;

        do {
            $reports = $this->reportRepository->findBy([], null, $batchSize, $offset);
            
            foreach ($reports as $report) 
            $this->cache->save($report,  $this->transformer->transform($report, $this->settingRepository->findById(1)));
            
            $offset += $batchSize;
        } while (count($reports) === $batchSize);

        $output->writeln('Reports have been successfully fetched and cached.');

        return Command::SUCCESS;
    }
}