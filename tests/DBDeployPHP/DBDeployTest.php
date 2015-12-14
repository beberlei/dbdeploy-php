<?php

namespace DBDeployPHP;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\ConnectionException;

class DBDeployTest extends \PHPUnit_Framework_TestCase
{
    static private $databaseName = 'dbdeploy_tests';

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function createConnection()
    {
        if (!isset($_SERVER['DBDEPLOY_TEST_DATABASE_URL'])) {
            $this->markTestSkipped('Requires DBDEPLOY_TEST_DATABASE_URL env variable for a connection (not directly to a database)');
        }

        $connection = DriverManager::getConnection(array(
            'url' => $_SERVER['DBDEPLOY_TEST_DATABASE_URL'],
        ));
        $connection->exec('CREATE DATABASE IF NOT EXISTS ' . self::$databaseName);
        $connection->exec('USE ' . self::$databaseName);

        return $connection;
    }

    /**
     * @test
     */
    public function it_migrates()
    {
        $connection = $this->createConnection();
        $schemaManager = $connection->getSchemaManager();

        $schemaManager->tryMethod('dropTable', 'foo');
        $schemaManager->tryMethod('dropTable', 'bar');
        $schemaManager->tryMethod('dropTable', 'changelog');

        $deploy = new DBDeploy($connection, __DIR__ . '/../schema1');
        $deploy->migrate();

        $this->assertEquals(array('changelog', 'foo'), $schemaManager->listTableNames());

        $deploy = new DBDeploy($connection, __DIR__ . '/../schema2');
        $deploy->migrate();

        $this->assertEquals(array('bar', 'changelog', 'foo'), $schemaManager->listTableNames());
    }

    /**
     * @test
     */
    public function it_requires_schema_manager()
    {
        $connection = DriverManager::getConnection(array('url' => 'sqlite://memory'));

        $this->setExpectedException('RuntimeException', 'SchemaDirectory ');

        new DBDeploy($connection, __DIR__ . '/doesnotexist');
    }

    /**
     * @test
     */
    public function it_disallows_duplicate_revisions()
    {
        $connection = $this->createConnection();

        $this->setExpectedException('RuntimeException', "Duplicate revision number '1' is not allowed.");

        $deploy = new DBDeploy($connection, __DIR__ . '/../schema3');
        $deploy->migrate();
    }

    /**
     * @test
     */
    public function it_disallows_undo_dbdeploy_files()
    {
        $connection = $this->createConnection();

        $this->setExpectedException('RuntimeException', 'No support for DBDeploy "--//@UNDO" feature.');

        $deploy = new DBDeploy($connection, __DIR__ . '/../schema4');
        $deploy->migrate();
    }

    /**
     * @test
     */
    public function it_natuarlly_sorts()
    {
        $connection = $this->createConnection();
        $schemaManager = $connection->getSchemaManager();

        $schemaManager->tryMethod('dropTable', 'foo');
        $schemaManager->tryMethod('dropTable', 'bar');
        $schemaManager->tryMethod('dropTable', 'changelog');

        $deploy = new DBDeploy($connection, __DIR__ . '/../schema5');
        $deploy->migrate();

        $migrations = array_map(function ($row) {
            return $row['description'];
        }, $connection->fetchAll('SELECT description FROM changelog'));

        $this->assertEquals(array('changelog'), $schemaManager->listTableNames());
        $this->assertEquals(array('001_create.sql', '002_drop.sql'), $migrations);
    }
}
