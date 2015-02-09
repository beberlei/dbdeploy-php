<?php
/**
 * DBDeploy PHP
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace DBDeployPHP;

use Doctrine\DBAL\Connection;

/**
 * Clone of DBDeploy for PHP and Doctrine DBAL.
 *
 * Only supports a limited set of functionality of the orignal DBDeploy.
 * Notable things not supported:
 *
 * - Output of the changes done
 * - UNDO syntax
 */
class DBDeploy
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $schemaDirectory;

    public function __construct(Connection $connection, $schemaDirectory)
    {
        if (!is_dir($schemaDirectory)) {
            throw new \RuntimeException(sprintf('SchemaDirectory "%s" variable is not a valid directory.', $schemaDirectory));
        }

        $this->connection = $connection;
        $this->schemaDirectory = $schemaDirectory;
    }

    /**
     * Migrate database to new version comparing changelog table and schema directory.
     *
     * @return array<string,array>
     */
    public function migrate()
    {
        $schemaManager = $this->connection->getSchemaManager();
        $tables = $schemaManager->listTableNames();

        if (!in_array('changelog', $tables)) {
            $table = new \Doctrine\DBAL\Schema\Table('changelog');
            $table->addColumn('change_number', 'integer', array('autoincrement' => true));
            $table->addColumn('complete_dt', 'datetime', array(
                'columnDefinition' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ));
            $table->addColumn('applied_by', 'string', array('length' => 100));
            $table->addcolumn('description', 'string', array('length' => 500));
            $table->setPrimaryKey(array('change_number'));

            $schemaManager->createTable($table);
        }

        $allMigrations = $this->getAllMigrations($this->schemaDirectory);
        $appliedMigrations = $this->getAppliedMigrations();
        $applyMigrations = array();

        foreach ($allMigrations as $revision => $data) {
            if (!isset($appliedMigrations[$revision])) {
                $applyMigrations[$revision] = $data;
            }
        }

        foreach ($applyMigrations as $revision => $data) {
            $this->connection->exec($data['sql']);
            $this->connection->insert(
                'changelog',
                array('description' => $data['description'], 'applied_by' => $data['applied_by'])
            );
        }

        return $applyMigrations;
    }

    private function getAllMigrations($path)
    {
        $files = glob($path . '/*.sql');
        $migrations = array();

        foreach ($files as $file) {
            $basefile = basename($file);
            $sql = file_get_contents($file);

            $revision = $this->getRevision($basefile);

            if (isset($migrations[$revision])) {
                throw new \RuntimeException(sprintf("Duplicate revision number '%d' is not allowed.", $revision));
            }

            if (strpos($sql, '--//@UNDO') !== false) {
                throw new \RuntimeException('No support for DBDeploy "--//@UNDO" feature.');
            }

            $migrations[$revision] = array(
                'sql' => $sql,
                'file' => $file,
                'description' => $basefile,
                'applied_by' => $this->connection->getUsername(),
            );
        }

        ksort($migrations, SORT_NATURAL);

        return $migrations;
    }

    private function getAppliedMigrations()
    {
        $appliedMigrations = array();

        $sql = 'SELECT * FROM changelog';
        $stmt = $this->connection->executeQuery($sql);

        while ($row = $stmt->fetch()) {
            $revision = $this->getRevision($row['description']);

            if (isset($appliedMigrations[$revision])) {
                throw new \RuntimeException(sprintf("Duplicate revision number '%d' is not allowed.", $revision));
            }

            $appliedMigrations[$revision] = $row;
        }

        return $appliedMigrations;
    }

    private function getRevision($basefile)
    {
        if (preg_match('((^[0-9]+)_(.*)$)', $basefile, $matches)) {
            return $matches[1];
        } else {
            throw new \RuntimeException(sprintf("No revision found in file '%s'.", $basefile));
        }
    }
}
