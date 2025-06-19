<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:server-init',
    description: 'Initialize the server.',
)]
class ServerInitCommand extends Command
{
    private array $commandsBase = [
        'app:ensure-schema',
        'doctrine:migrations:migrate',
        'doctrine:fixtures:load',
        'app:redisFlush',
        'app:fetchDocumentsToCache',
        'app:fetchReportsToCache',
        'app:fetchPropertiesToCache',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Initialize the server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        foreach ($this->commandsBase as $command) {
            $command = $this->getApplication()?->find($command);

            if (null === $command)
            return Command::FAILURE;

            $this->ignoreValidationErrors();
            $returnCode = $command->run($input, $output);

            if ($returnCode !== Command::SUCCESS) {
                return $returnCode;
            }
        }

        $output->writeln('Initialize successful.');

        return Command::SUCCESS;
    }
}