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

class MigrationStatus
{
    private $all;
    private $apply;
    private $applied;

    public function __construct(array $all, array $applied, array $apply)
    {
        $this->all = $all;
        $this->applied = $applied;
        $this->apply = $apply;
    }

    public function getAllMigrations()
    {
        return $this->all;
    }

    public function getApplyMigrations()
    {
        return $this->apply;
    }

    public function getAppliedMigrations()
    {
        return $this->applied;
    }
}
