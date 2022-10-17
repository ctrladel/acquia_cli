<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class CronCommandTest extends AcquiaCliTestCase
{
    /**
     * @dataProvider cronProvider
     * @param array<int, string> $command
     * @param string $expected
     */
    public function testCronCommands(array $command, string $expected): void
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    /**
     * @return array<array<mixed>>
     */
    public function cronProvider(): array
    {

        $cronList = <<<LIST
+----+-----------------------------------------------------------------------+------------+
| ID | Command                                                               | Frequency  |
+----+-----------------------------------------------------------------------+------------+
| 24 | /usr/local/bin/drush cc all                                           | 25 7 * * * |
| 25 | /usr/local/bin/drush -r /var/www/html/qa3/docroot ah-db-backup dbname | 12 9 * * * |
+----+-----------------------------------------------------------------------+------------+
>  Cron commands starting with "#" are disabled.
LIST;

        $cronInfo = <<<INFO
>  ID: 24
>  Label: 
>  Environment: dev
>  Command: /usr/local/bin/drush cc all
>  Frequency: 25 7 * * *
>  Enabled: ✓
>  System:  
>  On any web: ✓
INFO;

        return [
            [
                ['cron:create', 'devcloud:devcloud2', 'dev', 'commandString', 'frequency', 'label'],
                '>  Adding new cron task on dev environment' . PHP_EOL
            ],
            [
                ['cron:delete', 'devcloud:devcloud2', 'dev', 24],
                '>  Deleting cron task 24 from Dev' . PHP_EOL
            ],
            [
                ['cron:disable', 'devcloud:devcloud2', 'dev', 24],
                '>  Disabling cron task 24 on dev environment' . PHP_EOL
            ],
            [
                ['cron:enable', 'devcloud:devcloud2', 'dev', 24],
                '>  Enabling cron task 24 on dev environment' . PHP_EOL
            ],
            [
                ['cron:info', 'devcloud:devcloud2', 'dev', 24],
                $cronInfo . PHP_EOL
            ],
            [
                ['cron:list', 'devcloud:devcloud2', 'dev'],
                $cronList . PHP_EOL
            ]
        ];
    }
}
