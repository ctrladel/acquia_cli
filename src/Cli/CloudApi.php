<?php

namespace AcquiaCli\Cli;

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\ClientInterface;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Organizations;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Response\OrganizationResponse;
use Consolidation\Config\ConfigInterface;
use Robo\Config\Config;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class CloudApi
 *
 * @package AcquiaCli
 */
class CloudApi
{
    protected ClientInterface $client;

    protected ConfigInterface $config;

    public function __construct(Config $config, ?Client $client)
    {
        $this->config = $config;
        $this->setClient($client);
    }

    public static function createClient(?ConfigInterface $config): Client
    {

        $acquia = $config->get('acquia');

        $connector = new Connector(
            [
            'key' => $acquia['key'],
            'secret' => $acquia['secret'],
            ]
        );

        $client = Client::factory($connector);

        return $client;
    }

    /**
     * @param  string $name
     * @return mixed
     * @throws \Exception
     */
    public function getApplicationUuid($name)
    {
        $cacheId = sprintf('application.%s', str_replace(':', '.', $name));

        $cache = new FilesystemAdapter('acquiacli');
        $cache->deleteItem($cacheId);
        $return = $cache->get(
            $cacheId,
            function (ItemInterface $item) {
                $count = -1;
                $name = str_replace('application:', '', str_replace('.', ':', $item->getKey()));

                $app = new Applications($this->client);
                $applications = $app->getAll();

                foreach ($applications as $application) {
                    /** @phpstan-ignore-next-line */
                    if ($name === $application->hosting->id) {
                        return $application->uuid;
                    }
                }
                throw new \Exception('Unable to find UUID for application');
            }
        );

        return $return;
    }

    /**
     * @param  string $uuid
     * @param  string $environment
     * @return EnvironmentResponse
     * @throws \Exception
     */
    public function getEnvironment(string $uuid, string $environment)
    {
        $cacheId = sprintf('environment.%s.%s', $uuid, $environment);

        $cache = new FilesystemAdapter('acquiacli');
        $return = $cache->get(
            $cacheId,
            function (ItemInterface $item) {
                $split = explode('.', $item->getKey());
                $uuid = $split[1];
                $environment = $split[2];

                $environmentsAdapter = new Environments($this->client);
                $environments = $environmentsAdapter->getAll($uuid);

                foreach ($environments as $e) {
                    if ($environment === $e->name) {
                        return $e;
                    }
                }

                throw new \Exception('Unable to find environment from environment name');
            }
        );

        return $return;
    }

    /**
     * @param  string $organizationName
     * @return OrganizationResponse
     * @throws \Exception
     */
    public function getOrganization($organizationName)
    {
        $org = new Organizations($this->client);
        $organizations = $org->getAll();

        foreach ($organizations as $organization) {
            if ($organizationName === $organization->name) {
                return $organization;
            }
        }

        throw new \Exception('Unable to find organization from organization name');
    }

    public function getClient(): ClientInterface
    {
        /** @phpstan-ignore-next-line */
        if (!$this->client) {
            $client = self::createClient($this->config);
            $this->setClient($client);
        }
        return $this->client;
    }

    public function setClient(ClientInterface $client): void
    {
        $this->client = $client;
    }
}
