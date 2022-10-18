<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class DbCommandTest extends AcquiaCliTestCase
{
    /**
     * @dataProvider dbProvider
     * @param array<int, string> $command
     * @param string $expected
     */
    public function testDbCommands(array $command, string $expected): void
    {
        $actualResponse = $this->execute($command);
        $this->assertSameWithoutLE($expected, $actualResponse);
    }

    /**
     * @return array<array<mixed>>
     */
    public function dbProvider(): array
    {

        $dbTable = <<<TABLE
+-----------+
| Databases |
+-----------+
| database1 |
| database2 |
+-----------+
TABLE;

        $dbCopy = <<<TEXT
>  Backing up DB (database1) on Dev
>  Moving DB (database1) from Stage to Dev
>  Backing up DB (database2) on Dev
>  Moving DB (database2) from Stage to Dev
TEXT;

        $dbCopyNoBackup = <<<TEXT
>  Moving DB (database1) from Stage to Dev
>  Moving DB (database2) from Stage to Dev
TEXT;

        return [
            [
                ['database:create', 'devcloud:devcloud2', 'dbName'],
                '>  Creating database (dbName)' . PHP_EOL
            ],
            [
                ['database:delete', 'devcloud:devcloud2', 'dbName'],
                '>  Deleting database (dbName)' . PHP_EOL
            ],
            [
                ['database:list', 'devcloud:devcloud2'],
                $dbTable . PHP_EOL
            ],
            [
                ['database:truncate', 'devcloud:devcloud2', 'dbName'],
                '>  Truncate database (dbName)' . PHP_EOL
            ],
            [
                ['database:copy', 'devcloud:devcloud2', 'test', 'dev', 'dbName'],
                $dbCopy . PHP_EOL
            ],
            [
                ['database:copy:all', 'devcloud:devcloud2', 'test', 'dev'],
                $dbCopy . PHP_EOL
            ],
            [
                ['database:copy', 'devcloud:devcloud2', 'test', 'dev', 'dbName', '--no-backup'],
                $dbCopyNoBackup . PHP_EOL
            ],
            [
                ['database:copy:all', 'devcloud:devcloud2', 'test', 'dev', '--no-backup'],
                $dbCopyNoBackup . PHP_EOL
            ]
        ];
    }
}
