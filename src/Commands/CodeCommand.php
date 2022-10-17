<?php

namespace AcquiaCli\Commands;

use AcquiaCli\Cli\CloudApi;
use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Endpoints\Code;
use Symfony\Component\Console\Helper\Table;

/**
 * Class CodeCommand
 *
 * @package AcquiaCli\Commands
 */
class CodeCommand extends AcquiaCommand
{
    /**
     * Gets all code branches and tags associated with an application.
     *
     * @param string $uuid
     *
     * @command code:list
     * @aliases c:l
     */
    public function code(Client $client, Code $codeAdapter, $uuid): void
    {
        $branches = $codeAdapter->getAll($uuid);
        $client->clearQuery();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Name', 'Tag']);

        foreach ($branches as $branch) {
            /** @phpstan-ignore-next-line */
            $tag = $branch->flags->tag ? '✓' : '';
            $table
                ->addRows(
                    [
                    [
                        $branch->name,
                        $tag,
                    ],
                    ]
                );
        }

        $table->render();
    }

    /**
     * Deploys code from one environment to another.
     *
     * @param string $uuid
     * @param string $environmentFrom
     * @param string $environmentTo
     * @throws \Exception
     * @command code:deploy
     * @option  no-backup Do not backup the database(s) prior to deploying code.
     * @aliases c:d
     */
    public function codeDeploy(
        Code $codeAdapter,
        string $uuid,
        string $environmentFrom,
        string $environmentTo,
        ?array $options = ['no-backup']
    ): void {
        $environmentFrom = $this->cloudapiService->getEnvironment($uuid, $environmentFrom);
        $environmentTo = $this->cloudapiService->getEnvironment($uuid, $environmentTo);

        if (
            !$this->confirm(
                sprintf(
                    'Are you sure you want to deploy code from %s to %s?',
                    $environmentFrom->label,
                    $environmentTo->label
                )
            )
        ) {
            return;
        }

        if (!$options['no-backup']) {
            $this->backupAllEnvironmentDbs($uuid, $environmentTo);
        }

        $this->say(
            sprintf(
                'Deploying code from the %s environment to the %s environment',
                $environmentFrom->label,
                $environmentTo->label
            )
        );

        $response = $codeAdapter->deploy($environmentFrom->uuid, $environmentTo->uuid);
        $this->waitForNotification($response);
    }

    /**
     * Switches code branch on an environment.
     *
     * @param string $uuid
     * @param string $environment
     * @param string $branch
     * @throws \Exception
     * @command code:switch
     * @option  no-backup Do not backup the database(s) prior to switching code.
     * @aliases c:s
     */
    public function codeSwitch(
        CloudApi $cloudapi,
        Code $codeAdapter,
        string $uuid,
        string $environment,
        string $branch,
        ?array $options = ['no-backup']
    ): void {
        $environment = $cloudapi->getEnvironment($uuid, $environment);

        if (
            !$this->confirm(
                sprintf(
                    'Are you sure you want to switch code on the %s environment to branch: %s?',
                    $environment->name,
                    $branch
                )
            )
        ) {
            return;
        }

        if (!$options['no-backup']) {
            $this->backupAllEnvironmentDbs($uuid, $environment);
        }

        $this->say(sprintf('Switching %s enviroment to %s branch', $environment->label, $branch));

        $response = $codeAdapter->switch($environment->uuid, $branch);
        $this->waitForNotification($response);
    }
}
