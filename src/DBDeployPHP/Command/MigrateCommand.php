<?php

namespace DBDeployPHP\Command;

use DBDeployPHP\DBDeploy;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Doctrine\DBAL\DriverManager;
use RuntimeException;

class MigrateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Migrate a given database to the latest version.')
            ->addArgument('schema-dir', InputArgument::REQUIRED)
            ->addOption('dsn', 'd', InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directory = $input->getArgument('schema-dir');

        if (strpos($directory, '/') !== 0) {
            $directory = getcwd() . '/' . $directory;
        }

        if (!file_exists($directory)) {
            throw new RuntimeException(sprintf("Schema directory does not exist at '%s'.", $directory));
        }

        $dsn = $input->getOption('dsn');

        if (isset($_SERVER['DATABASE_URL'])) {
            $dsn = $_SERVER['DATABASE_URL'];
        }

        if (!$dsn) {
            throw new RuntimeException("Missing environment variable DATABASE_URL in format mysql://user:password@host/database");
        }

        $connection = DriverManager::getConnection(array('url' => $_SERVER['DATABASE_URL']));
        $migrator = new DBDeploy($connection, $directory);

        $output->writeln(sprintf("Reading change scripts from directory %s... \n", $directory));

        $status = $migrator->getCurrentStatus();

        $appliedString = $status->getAppliedMigrations() ? implode(', ', array_keys($status->getAppliedMigrations())) : '(none)';
        $applyString = $status->getApplyMigrations() ? implode(', ', array_keys($status->getApplyMigrations())) : '(none)';

        $output->writeln(sprintf("Changes currently applied to database:\n  %s\n", $appliedString));
        $output->writeln(sprintf("To be applied:\n  %s", $applyString));

        $migrator->apply($status);
    }
}
