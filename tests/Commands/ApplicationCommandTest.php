<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class ApplicationCommandTest extends AcquiaCliTestCase
{
    /**
     * @dataProvider applicationProvider
     * @param array<int, string> $command
     * @param string $expected
     */
    public function testApplicationCommands(array $command, string $expected): void
    {
        $actualResponse = $this->execute($command);
        $this->assertSameWithoutLE($expected, $actualResponse);
    }

    public function testApplicationInfoCommand(): void
    {
        $result = $this->execute(['application:info', 'devcloud:devcloud2']);

        $this->assertStringContainsString('Dev (dev)', $result);
        $this->assertStringContainsString('🔒  Production (prod)', $result);
        $this->assertStringContainsString('Stage (test)', $result);
        $this->assertStringContainsString('24-a47ac10b-58cc-4372-a567-0e02b2c3d470', $result);
        $this->assertStringContainsString('15-a47ac10b-58cc-4372-a567-0e02b2c3d470', $result);
        $this->assertStringContainsString('32-a47ac10b-58cc-4372-a567-0e02b2c3d470', $result);
        $this->assertStringContainsString('master', $result);
        $this->assertStringContainsString('tags/01-01-2015', $result);
        $this->assertStringContainsString('sitedev.hosted.acquia-sites.com', $result);
        $this->assertStringContainsString('siteprod.hosted.acquia-sites.com', $result);
        $this->assertStringContainsString('sitetest.hosted.acquia-sites.com', $result);
        $this->assertStringContainsString('example.com', $result);
        $this->assertStringContainsString('test.example.com', $result);
        $this->assertStringContainsString('database1', $result);
        $this->assertStringContainsString('database2', $result);
        $this->assertStringContainsString('qa10@svn-3.networkdev.ahserversdev.com:qa10.git', $result);
    }

    /**
     * @return array<array<mixed>>
     */
    public function applicationProvider(): array
    {
        $getAllApplications = <<<TABLE
+----------------------+--------------------------------------+--------------------+
| Name                 | UUID                                 | Hosting ID         |
+----------------------+--------------------------------------+--------------------+
| Sample application 1 | a47ac10b-58cc-4372-a567-0e02b2c3d470 | devcloud:devcloud2 |
| Sample application 2 | a47ac10b-58cc-4372-a567-0e02b2c3d471 | devcloud:devcloud2 |
+----------------------+--------------------------------------+--------------------+
TABLE;

        // phpcs:disable Generic.Files.LineLength.TooLong
        $applicationInfo = <<<TABLE
+-----------------------+-----------------------------------------+-----------------+----------------------------------+-------------+
| Environment           | ID                                      | Branch/Tag      | Domain(s)                        | Database(s) |
+-----------------------+-----------------------------------------+-----------------+----------------------------------+-------------+
| Dev (dev)             | 24-a47ac10b-58cc-4372-a567-0e02b2c3d470 | master          | sitedev.hosted.acquia-sites.com  | database1   |
|                       |                                         |                 | example.com                      | database2   |
| 🔒  Production (prod) | 15-a47ac10b-58cc-4372-a567-0e02b2c3d470 | tags/01-01-2015 | siteprod.hosted.acquia-sites.com | database1   |
|                       |                                         |                 | example.com                      | database2   |
| Stage (test)          | 32-a47ac10b-58cc-4372-a567-0e02b2c3d470 |                 | sitetest.hosted.acquia-sites.com | database1   |
|                       |                                         |                 | test.example.com                 | database2   |
+-----------------------+-----------------------------------------+-----------------+----------------------------------+-------------+
>  🔧  Git URL: qa10@svn-3.networkdev.ahserversdev.com:qa10.git
>  💻  indicates environment in livedev mode.
>  🔒  indicates environment in production mode.
TABLE;
        // phpcs:enable

        $getTags = <<<TABLE
+------+--------+
| Name | Color  |
+------+--------+
| Dev  | orange |
+------+--------+
TABLE;

        return [
            [
                ['application:list'],
                $getAllApplications . PHP_EOL
            ],
//            [
//                ['application:info', 'devcloud:devcloud2'],
//                $applicationInfo . PHP_EOL
//            ],
            [
                ['application:tags', 'devcloud:devcloud2'],
                $getTags . PHP_EOL
            ],
            [
                ['application:tag:create', 'devcloud:devcloud2', 'name', 'color'],
                '>  Creating application tag name:color' . PHP_EOL
            ],
            [
                ['application:tag:delete', 'devcloud:devcloud2', 'name'],
                '>  Deleting application tag name' . PHP_EOL
            ],
            [
                ['application:rename', 'devcloud:devcloud2', 'foobar'],
                '>  Renaming application to foobar' . PHP_EOL
            ]
        ];
    }
}
