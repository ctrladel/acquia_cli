<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\Domains;
use AcquiaCloudApi\Response\DomainResponse;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DomainCommand
 *
 * @package AcquiaCli\Commands
 */
class DomainCommand extends AcquiaCommand
{
    /**
     * Lists domains.
     *
     * @param string $uuid
     * @param string $environment
     *
     * @throws \Exception
     * @command domain:list
     */
    public function domainList(OutputInterface $output, Domains $domainAdapter, string $uuid, string $environment): void
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        $domains = $domainAdapter->getAll($environment->uuid);

        $table = new Table($output);
        $table->setHeaders(['Hostname', 'Default', 'Active', 'Uptime']);
        $table->setColumnStyle(1, 'center-align');
        $table->setColumnStyle(2, 'center-align');
        $table->setColumnStyle(3, 'center-align');

        foreach ($domains as $domain) {
            /**
             * @var DomainResponse $domain
             */
            $table
                ->addRows(
                    [
                    [
                        $domain->hostname,
                        /** @phpstan-ignore-next-line */
                        $domain->flags->default ? '✓' : '',
                        /** @phpstan-ignore-next-line */
                        $domain->flags->active ? '✓' : '',
                        /** @phpstan-ignore-next-line */
                        $domain->flags->uptime ? '✓' : '',
                    ],
                    ]
                );
        }

        $table->render();
    }

    /**
     * Gets information about a domain.
     *
     * @param string $uuid
     * @param string $environment
     * @param string $domain
     *
     * @throws \Exception
     * @command domain:info
     */
    public function domainInfo(OutputInterface $output, Domains $domainAdapter, string $uuid, string $environment, string $domain): void
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        $domain = $domainAdapter->status($environment->uuid, $domain);

        $table = new Table($output);
        $table->setHeaders(['Hostname', 'Active', 'DNS Resolves', 'IP Addresses', 'CNAMES']);
        $table->setColumnStyle(1, 'center-align');
        $table->setColumnStyle(2, 'center-align');
        $table
            ->addRows(
                [
                [
                    $domain->hostname,
                    /** @phpstan-ignore-next-line */
                    $domain->flags->active ? '✓' : '',
                    /** @phpstan-ignore-next-line */
                    $domain->flags->dns_resolves ? '✓' : '',
                    implode("\n", $domain->ip_addresses),
                    implode("\n", $domain->cnames),
                ],
                ]
            );

        $table->render();
    }

    /**
     * Add a domain to an environment.
     *
     * @param string $uuid
     * @param string $environment
     * @param string $domain
     *
     * @throws \Exception
     * @command domain:create
     * @aliases domain:add
     */
    public function domainCreate(Domains $domainAdapter, string $uuid, string $environment, string $domain): void
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        $this->say(sprintf('Adding %s to environment %s', $domain, $environment->label));
        $response = $domainAdapter->create($environment->uuid, $domain);
        $this->waitForNotification($response);
    }

    /**
     * Remove a domain to an environment.
     *
     * @param string $uuid
     * @param string $environment
     * @param string $domain
     *
     * @command domain:delete
     * @aliases domain:remove
     */
    public function domainDelete(Domains $domainAdapter, string $uuid, string $environment, string $domain): void
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        if ($this->confirm('Are you sure you want to remove this domain?')) {
            $this->say(sprintf('Removing %s from environment %s', $domain, $environment->label));
            $response = $domainAdapter->delete($environment->uuid, $domain);
            $this->waitForNotification($response);
        }
    }

    /**
     * Move a domain from one environment to another.
     *
     * @param string $uuid
     * @param string $domain
     * @param string $environmentFrom
     * @param string $environmentTo
     *
     * @throws \Exception
     * @command domain:move
     */
    public function domainMove(Domains $domainAdapter, string $uuid, string $domain, string $environmentFrom, string $environmentTo): void
    {
        $environmentFrom = $this->cloudapiService->getEnvironment($uuid, $environmentFrom);
        $environmentTo = $this->cloudapiService->getEnvironment($uuid, $environmentTo);

        if (
            $this->confirm(
                sprintf(
                    'Are you sure you want to move %s from environment %s to %s?',
                    $domain,
                    $environmentFrom->label,
                    $environmentTo->label
                )
            )
        ) {
            $this->say(sprintf('Moving %s from %s to %s', $domain, $environmentFrom->label, $environmentTo->label));

            $deleteResponse = $domainAdapter->delete($environmentFrom->uuid, $domain);
            $this->waitForNotification($deleteResponse);

            $addResponse = $domainAdapter->create($environmentTo->uuid, $domain);
            $this->waitForNotification($addResponse);
        }
    }

    /**
     * Clears varnish cache for a specific domain.
     *
     * @param string $uuid
     * @param string $environment
     * @param string|null $domain
     * @throws \Exception
     * @command domain:purge
     */
    public function domainPurge(Domains $domainAdapter, string $uuid, string $environment, ?string $domain = null): void
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);

        if (
            $environment->name === 'prod'
            && !$this->confirm("Are you sure you want to purge varnish on the production environment?")
        ) {
            return;
        }

        if (null === $domain) {
            $domains = $domainAdapter->getAll($environment->uuid);
            $domainNames = array_map(
                function ($domain) {
                    $this->say(sprintf('Purging domain: %s', $domain->hostname));
                    return $domain->hostname;
                },
                $domains->getArrayCopy()
            );
        } else {
            $this->say(sprintf('Purging domain: %s', $domain));
            $domainNames = [$domain];
        }

        $response = $domainAdapter->purge($environment->uuid, $domainNames);
        $this->waitForNotification($response);
    }
}
