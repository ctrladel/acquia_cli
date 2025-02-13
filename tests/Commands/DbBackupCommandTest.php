<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class DbBackupCommandTest extends AcquiaCliTestCase
{
    public function testDownloadDatabaseBackupsCommands(): void
    {
        $command = ['database:backup:download', 'devcloud:devcloud2', 'dev', 'database2'];
        $actualResponse = $this->execute($command);

// @TODO linux
//>  Downloading database backup to /tmp/dev-database2-1cIgqB0.sql.gz
//    0 [➤⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬]
//>  Database backup downloaded to /tmp/dev-database2-1cIgqB0.sql.gz
// @TODO mac
//>  Downloading database backup to /private/var/folders/24/8k48jl6d249_n_qfxwsl6xvm0000gn/T/dev-database2-1FQ2SUG.sql.gz
//    0 [➤⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬]
//>  Database backup downloaded to /private/var/folders/24/8k48jl6d249_n_qfxwsl6xvm0000gn/T/dev-database2-1FQ2SUG.sql.gz
// @TODO windows
//>  Downloading database backup to C:\Users\runneradmin\AppData\Local\Temp\devB759.tmp.sql.gz
//    0 [➤⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬]
//>  Database backup downloaded to C:\Users\runneradmin\AppData\Local\Temp\devB759.tmp.sql.gz
// @TODO fix regex to work with the windows filesystem. This currently only works with mac and linux.

//        $this->assertEquals(
//            preg_match(
//                '@>  Downloading database backup to ((\S+)dev-database2-(\w+).sql.gz)@',
//                $actualResponse,
//                $matches
//            ),
//            1
//        );

        $this->assertStringStartsWith('>  Downloading database backup to ', $actualResponse);
//        $this->assertStringContainsString(sys_get_temp_dir(), $matches[2]);
    }

    public function testDownloadDatabaseBackupsCommandsWithOptions(): void
    {
        $command = [
            'database:backup:download',
            'devcloud:devcloud2',
            'dev',
            'database2',
            '--backup=1',
            '--filename=foo',
            '--path=/tmp'
        ];
        $actualResponse = $this->execute($command);

// @TODO linux
//>  Downloading database backup to /tmp/foo.sql.gz
//    0 [➤⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬]
//>  Database backup downloaded to /tmp/foo.sql.gz
// @TODO mac
//>  Downloading database backup to /tmp/foo.sql.gz
//    0 [➤⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬]
//>  Database backup downloaded to /tmp/foo.sql.gz
// @TODO windows
//>  Downloading database backup to /tmp\foo.sql.gz
//    0 [➤⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬]
//>  Database backup downloaded to /tmp\foo.sql.gz
// @TODO fix regex to work with the windows filesystem. This currently only works with mac and linux.

//        $this->assertEquals(
//            preg_match(
//                '@>  Downloading database backup to ((/tmp/)foo.sql.gz)@',
//                $actualResponse,
//                $matches
//            ),
//            1
//        );

        $this->assertStringStartsWith('>  Downloading database backup to ', $actualResponse);
//        $this->assertStringContainsString('/tmp/', $matches[2]);
    }

    /**
     * @dataProvider dbBackupProvider
     * @param array<int, string> $command
     * @param string $expected
     */
    public function testDbBackupCommands(array $command, string $expected): void
    {
        $actualResponse = $this->execute($command);
        $this->assertSameWithoutLE($expected, $actualResponse);
    }

    /**
     * @return array<array<mixed>>
     */
    public function dbBackupProvider(): array
    {

        $dbBackupList = <<<TABLE
+-----+-------+----------------------+
| ID  | Type  | Timestamp            |
+-----+-------+----------------------+
| database1                          |
+-----+-------+----------------------+
| 1   | Daily | 2012-05-15T12:00:00Z |
| 2   | Daily | 2012-03-28T12:00:01Z |
| 3   | Daily | 2017-01-08T04:00:01Z |
| 4   | Daily | 2017-01-08T05:00:03Z |
| database2                          |
+-----+-------+----------------------+
| 1   | Daily | 2012-05-15T12:00:00Z |
| 2   | Daily | 2012-03-28T12:00:01Z |
| 3   | Daily | 2017-01-08T04:00:01Z |
| 4   | Daily | 2017-01-08T05:00:03Z |
+-----+-------+----------------------+
TABLE;

        $createBackupAllText = '>  Backing up DB (database1) on Dev
>  Backing up DB (database2) on Dev';

        $createBackupText = '>  Backing up DB (database1) on Dev';

        $dbLink = sprintf(
            '%s/environments/%s/databases/%s/backups/%s/actions/download',
            '>  https://cloud.acquia.com/api',
            '24-a47ac10b-58cc-4372-a567-0e02b2c3d470',
            'dbName',
            1234
        );

        return [
            [
                ['database:backup:restore', 'devcloud:devcloud2', 'dev', 'dbName', '1234'],
                '>  Restoring backup 1234 to dbName on Dev' . PHP_EOL
            ],
            [
                ['database:backup:all', 'devcloud:devcloud2', 'dev'],
                $createBackupAllText . PHP_EOL
            ],
            [
                ['database:backup', 'devcloud:devcloud2', 'dev', 'database1'],
                $createBackupText . PHP_EOL
            ],
            [
                ['database:backup:list', 'devcloud:devcloud2', 'dev'],
                $dbBackupList . PHP_EOL
            ],
            [
                ['database:backup:list', 'devcloud:devcloud2', 'dev', 'dbName'],
                $dbBackupList . PHP_EOL
            ],
            [
                ['database:backup:link', 'devcloud:devcloud2', 'dev', 'dbName', '1234'],
                $dbLink . PHP_EOL
            ],
            [
                ['database:backup:delete', 'devcloud:devcloud2', 'dev', 'dbName', '1234'],
                '>  Deleting backup 1234 to dbName on Dev' . PHP_EOL
            ],
        ];
    }
}
