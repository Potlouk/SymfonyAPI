<?php
namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:ensure-schema',
    description: 'Ensures that the public schema exists',
    aliases: ['app:ensure-schema'],
    hidden: false
)]
class EnsureSchemaCommand extends Command
{
    private Connection $connection;
    private ParameterBagInterface $params;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params)
    {
        parent::__construct();
        $this->params = $params;
        
        $env = $params->get('kernel.environment');
        $databaseUrl = $env === 'test' ? $_ENV['DATABASE_TEST_URL'] : $_ENV['DATABASE_URL'];

        $this->connection = DriverManager::getConnection(['url' => $databaseUrl]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->connection->executeStatement('CREATE SCHEMA IF NOT EXISTS public');
        $this->connection->executeStatement('SET search_path TO public');
        $output->writeln('<info>Schema ensured in ' . $this->params->get('kernel.environment') . ' environment</info>');

        return Command::SUCCESS;
    }
}