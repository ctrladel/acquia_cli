<?php

namespace AcquiaCli\Commands;

use Exception;
use Robo\Tasks;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Class SetupCommand
 *
 * @package AcquiaCli\Commands
 * @throws  Exception
 */
class CacheClearCommand extends Tasks
{
    /**
     * Clears the application and environment caches that are stored on disk. This command can be used if other commands
     * are returning cached information that does not accurately reflect the data from Acquia Cloud.
     *
     * Data is cached indefinitely so the cache must be cleared if fundamental data about the application or environment
     * changes on the cloud.
     *
     * @command cache:clear
     * @aliases cc,cr
     */
    public function clearCache(): void
    {
        $cache = new FilesystemAdapter('acquiacli');
        if ($cache->clear()) {
            $this->say('AcquiaCli cache has been cleared');
        } else {
            throw new Exception('Problem clearing AcquiaCli cache.');
        }
    }
}
