<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class VariablesCommandTest extends AcquiaCliTestCase
{
    /**
     * @dataProvider variablesProvider
     * @param array<int, string> $command
     * @param string $expected
     */
    public function testVariablesCommands(array $command, string $expected): void
    {
        $actualResponse = $this->execute($command);
        $this->assertSameWithoutLE($expected, $actualResponse);
    }

    /**
     * @return array<array<mixed>>
     */
    public function variablesProvider(): array
    {

        $variablesList = <<<TABLE
+----------------+--------------------+
| Name           | Value              |
+----------------+--------------------+
| variable_one   | Sample Value One   |
| variable_two   | Sample Value Two   |
| variable_three | Sample Value Three |
+----------------+--------------------+
TABLE;

        return [
            [
                ['variable:create', 'devcloud:devcloud2', 'dev', 'variable_one', 'Sample Value One'],
                '>  Adding variable variable_one:Sample Value One to Dev environment' . PHP_EOL
            ],
            [
                ['variable:delete', 'devcloud:devcloud2', 'dev', 'variable_one'],
                '>  Removing variable variable_one from Dev environment' . PHP_EOL
            ],
            [
                ['variable:info', 'devcloud:devcloud2', 'dev', 'variable_one'],
                '>  Sample Value One' . PHP_EOL
            ],
            [
                ['variable:list', 'devcloud:devcloud2', 'dev'],
                $variablesList . PHP_EOL
            ],
            [
                ['variable:update', 'devcloud:devcloud2', 'dev', 'variable_one', 'Sample Value One'],
                '>  Updating variable variable_one:Sample Value One on Dev environment' . PHP_EOL
            ]
        ];
    }
}
