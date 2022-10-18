<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class LiveDevCommandTest extends AcquiaCliTestCase
{
    /**
     * @dataProvider liveDevProvider
     * @param array<int, string> $command
     * @param string $expected
     */
    public function testLiveDevInfo(array $command, string $expected): void
    {
        $actualResponse = $this->execute($command);
        $this->assertSameWithoutLE($expected, $actualResponse);
    }

    /**
     * @return array<array<mixed>>
     */
    public function liveDevProvider(): array
    {

        return [
            [
                ['livedev:enable', 'devcloud:devcloud2', 'dev'],
                '>  Enabling livedev for Dev environment' . PHP_EOL
            ],
            [
                ['livedev:disable', 'devcloud:devcloud2', 'dev'],
                '>  Disabling livedev for Dev environment' . PHP_EOL
            ]
        ];
    }
}
