<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class TeamsCommandTest extends AcquiaCliTestCase
{
    /**
     * @dataProvider teamsProvider
     * @param array<int, string> $command
     * @param string $expected
     */
    public function testTeamsCommands(array $command, string $expected): void
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    /**
     * @return array<array<mixed>>
     */
    public function teamsProvider(): array
    {

        return [
            [
                ['team:addapplication', 'devcloud:devcloud2', 'teamUuid'],
                '>  Adding application to team.' . PHP_EOL
            ],
            [
                ['team:create', 'Sample organization', 'name'],
                '>  Creating new team.' . PHP_EOL
            ],
            [
                ['team:invite', 'teamUuid', 'email', 'roles'],
                '>  Inviting email to team.' . PHP_EOL
            ]
        ];
    }
}
