<?php

namespace AcquiaCli\Commands;

/**
 * Class DeployCommand
 *
 * @package AcquiaCli\Commands
 */
class DeployCommand extends AcquiaCommand
{
    /**
     * Prepares a non-production environment for a deployment by pulling back
     * the production database and files to the non-prod environment.
     *
     * @param string $uuid
     * @param string $environmentTo
     * @param string|null $environmentFrom
     *
     * @throws \Exception
     * @command deploy:prepare
     */
    public function deployPrepare(string $uuid, string $environmentTo, ?string $environmentFrom = null): void
    {
        if ($environmentTo === 'prod') {
            throw new \Exception('Cannot use deploy:prepare on the production environment');
        }

        if ($environmentFrom === null) {
            $environmentFrom = $this->cloudapiService->getEnvironment($uuid, 'prod');
        } else {
            $environmentFrom = $this->cloudapiService->getEnvironment($uuid, $environmentFrom);
        }

        $environmentTo = $this->cloudapiService->getEnvironment($uuid, $environmentTo);

        $this->moveDbs($uuid, $environmentFrom, $environmentTo);
        $this->copyFiles($uuid, $environmentFrom, $environmentTo);
    }
}
