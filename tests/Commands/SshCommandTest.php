<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class SshCommandTest extends AcquiaCliTestCase
{
    /**
     * @dataProvider sshProvider
     * @param array<int, string> $command
     * @param string $expected
     */
    public function testSshInfo(array $command, string $expected): void
    {
        $actualResponse = $this->execute($command);
        $this->assertSameWithoutLE($expected, $actualResponse);
    }

    /**
     * @return array<array<mixed>>
     */
    public function sshProvider(): array
    {

        $infoResponse = <<<INFO
>  dev: ssh 
>  prod: ssh 
>  test: ssh 
INFO;

        return [
            [
                ['ssh:info', 'devcloud:devcloud2'],
                $infoResponse . PHP_EOL
            ],
            [
                ['ssh:info', 'devcloud:devcloud2', 'dev'],
                $infoResponse . PHP_EOL,
            ]
        ];
    }
}
