<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:server-fetch-cache',
    description: 'fetch db data to cache storage',
)]
class ServerFetchToCacheCommand extends Command
{
    private array $commandsBase = [
        'app:fetchDocumentsToCache',
        'app:fetchReportsToCache',
        'app:fetchPropertiesToCache',
        'app:fetchSharedDocumentsToCache',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Initialize the server.');
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        foreach ($this->commandsBase as $command) {
            $command = $this->getApplication()?->find($command);

            if (null === $command)
                return Command::FAILURE;

            $returnCode = $command->run($input, $output);

            if ($returnCode !== Command::SUCCESS) {
                return $returnCode;
            }
        }

        $output->writeln('StartUp successful.');

        return Command::SUCCESS;
    }
}