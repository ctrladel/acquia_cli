<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class FilesCommandTest extends AcquiaCliTestCase
{
    /**
     * @dataProvider filesProvider
     * @param array<int, string> $command
     * @param string $expected
     */
    public function testFilesCommands(array $command, string $expected): void
    {
        $actualResponse = $this->execute($command);
        $this->assertSameWithoutLE($expected, $actualResponse);
    }

    /**
     * @return array<array<mixed>>
     */
    public function filesProvider(): array
    {

        return [
            [
                ['files:copy', 'devcloud:devcloud2', 'dev', 'test'],
                '>  Copying files from Dev to Stage' . PHP_EOL
            ]
        ];
    }
}
