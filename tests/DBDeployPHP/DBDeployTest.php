<?php

namespace DBDeployPHP;

use Doctrine\DBAL\DriverManager;

class DBDeployTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_migrates()
    {
        if (!isset($_SERVER['DATABASE_URL'])) {
            $this->markTestSkipped("Missing DATABASE_URL export.");
        }

        $connection = DriverManager::getConnection(array(
            'url' => $_SERVER['DATABASE_URL'],
        ));
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
        if (!isset($_SERVER['DATABASE_URL'])) {
            $this->markTestSkipped("Missing DATABASE_URL export.");
        }

        $connection = DriverManager::getConnection(array(
            'url' => $_SERVER['DATABASE_URL'],
        ));

        $this->setExpectedException('RuntimeException', "Duplicate revision number '1' is not allowed.");

        $deploy = new DBDeploy($connection, __DIR__ . '/../schema3');
        $deploy->migrate();
    }

    /**
     * @test
     */
    public function it_disallows_undo_dbdeploy_files()
    {
        if (!isset($_SERVER['DATABASE_URL'])) {
            $this->markTestSkipped("Missing DATABASE_URL export.");
        }

        $connection = DriverManager::getConnection(array(
            'url' => $_SERVER['DATABASE_URL'],
        ));

        $this->setExpectedException('RuntimeException', 'No support for DBDeploy "--//@UNDO" feature.');

        $deploy = new DBDeploy($connection, __DIR__ . '/../schema4');
        $deploy->migrate();
    }

    /**
     * @test
     */
    public function it_natuarlly_sorts()
    {
        if (!isset($_SERVER['DATABASE_URL'])) {
            $this->markTestSkipped("Missing DATABASE_URL export.");
        }

        $connection = DriverManager::getConnection(array(
            'url' => $_SERVER['DATABASE_URL'],
        ));
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
