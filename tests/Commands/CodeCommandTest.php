<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class CodeCommandTest extends AcquiaCliTestCase
{
    /**
     * @dataProvider codeProvider
     * @param array<int, string> $command
     * @param string $expected
     */
    public function testCodeCommands(array $command, string $expected): void
    {
        $actualResponse = $this->execute($command);
        $this->assertSameWithoutLE($expected, $actualResponse);
    }

    /**
     * @return array<array<mixed>>
     */
    public function codeProvider(): array
    {

        $codeList = <<<LIST
+-------------------+-----+
| Name              | Tag |
+-------------------+-----+
| master            |     |
| feature-branch    |     |
| tags/2014-09-03   | ✓   |
| tags/2014-09-03.0 | ✓   |
+-------------------+-----+
LIST;

        $codeDeploy = '>  Backing up DB (database1) on Stage
>  Backing up DB (database2) on Stage
>  Deploying code from the Dev environment to the Stage environment';

        $codeDeployNoBackup = '>  Deploying code from the Dev environment to the Stage environment';

        $codeSwitch = '>  Backing up DB (database1) on Production
>  Backing up DB (database2) on Production
>  Switching Production enviroment to master branch';

        $codeSwitchNoBackup = '>  Switching Production enviroment to master branch';

        return [
            [
                ['code:deploy', 'devcloud:devcloud2', 'dev', 'test'],
                $codeDeploy . PHP_EOL
            ],
            [
                ['code:deploy', 'devcloud:devcloud2', 'dev', 'test', '--no-backup'],
                $codeDeployNoBackup . PHP_EOL
            ],
            [
                ['code:list', 'devcloud:devcloud2'],
                $codeList . PHP_EOL
            ],
            [
                ['code:switch', 'devcloud:devcloud2', 'prod', 'master'],
                $codeSwitch . PHP_EOL
            ],
            [
                ['code:switch', 'devcloud:devcloud2', 'prod', 'master', '--no-backup'],
                $codeSwitchNoBackup . PHP_EOL
            ]
        ];
    }
}
