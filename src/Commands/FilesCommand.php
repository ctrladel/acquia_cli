<?php

namespace AcquiaCli\Commands;

/**
 * Class FilesCommand
 *
 * @package AcquiaCli\Commands
 */
class FilesCommand extends AcquiaCommand
{
    /**
     * Copies files from one environment to another.
     *
     * @param string $uuid
     * @param string $environmentFrom
     * @param string $environmentTo
     *
     * @throws \Exception
     * @command files:copy
     * @aliases f:c
     */
    public function filesCopy(string $uuid, string $environmentFrom, string $environmentTo): void
    {
        $environmentFrom = $this->cloudapiService->getEnvironment($uuid, $environmentFrom);
        $environmentTo = $this->cloudapiService->getEnvironment($uuid, $environmentTo);

        if (
            $this->confirm(
                sprintf(
                    'Are you sure you want to copy files from %s to %s?',
                    $environmentFrom->label,
                    $environmentTo->label
                )
            )
        ) {
            $this->copyFiles($uuid, $environmentFrom, $environmentTo);
        }
    }
}
