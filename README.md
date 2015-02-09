# DBDeploy PHP

[![Build Status](https://travis-ci.org/beberlei/dbdeploy-php.svg?branch=master)](https://travis-ci.org/beberlei/dbdeploy-php)

This is a clone of [DBDeploy](http://dbdeploy.com) for PHP using Doctrine DBAL
as database abstraction.

It supports only a limited set of functionality of the original Java based
tool, but enough for one particular workflow to function perfectly.

Why? This is extracted from a testsuite where it was used for setting up the
schema of the testing database. It is also much easier to setup than DBDeploy.

## Workflow Assumptions

* Only .sql file based migrations, format `<number>_<name>.sql`
* Requires using backwards-compatible database changes, no support undo/down
  migrations. Especially
    * Avoid dropping stuff
    * Added column must either allow NULL or have a default value
* Orders migrations (natural sort) using number prefixes in files. Use
  `YYmmddHHii_<name>.sql` format to allow branching without conflicts.
* Single database vendor per directory, use multiple for apps with different
  vendor support.
* Creates table `changelog` that contains current state of already applied migrations.

## API

The API just has one method: `migrate()`:

```php
<?php

use Doctrine\DBAL\DriverManager;
use DBDeployPHP\DBDeploy;

$dbDeploy = new DBDeploy($connection, $schemaDirectory);
$appliedMigrations = $dbDeploy->migrate();
```
## CLI

You need environment variable `DATABASE_URL` present with the format: `mysql://user:password@host/dbname`:

    $ php vendor/bin/dbdeploy-migrate.php src/schema

## Limitations

Currently only works with MySQL.

## License

The MIT License (MIT)

Copyright (c) 2015 Benjamin Eberlei

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
