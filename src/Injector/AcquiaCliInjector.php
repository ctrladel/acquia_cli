<?php

namespace AcquiaCli\Injector;

use AcquiaCloudApi\Endpoints\Account;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Code;
use AcquiaCloudApi\Endpoints\Crons;
use AcquiaCloudApi\Endpoints\DatabaseBackups;
use AcquiaCloudApi\Endpoints\Databases;
use AcquiaCloudApi\Endpoints\Domains;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Ides;
use AcquiaCloudApi\Endpoints\Insights;
use AcquiaCloudApi\Endpoints\LogForwardingDestinations;
use AcquiaCloudApi\Endpoints\Logs;
use AcquiaCloudApi\Endpoints\Notifications;
use AcquiaCloudApi\Endpoints\Organizations;
use AcquiaCloudApi\Endpoints\Permissions;
use AcquiaCloudApi\Endpoints\Roles;
use AcquiaCloudApi\Endpoints\Servers;
use AcquiaCloudApi\Endpoints\SslCertificates;
use AcquiaCloudApi\Endpoints\Teams;
use AcquiaCloudApi\Endpoints\Variables;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\ParameterInjector;

class AcquiaCliInjector implements ParameterInjector
{
    protected mixed $config;
    protected mixed $cloudapi;
    protected mixed $client;
    protected mixed $logstream;

    public function __construct()
    {
        $this->config = \Robo\Robo::service('config');
        $this->cloudapi = \Robo\Robo::service('cloudApi');
        $this->client = \Robo\Robo::service('client');
        $this->logstream = \Robo\Robo::service('logstream');
    }

    /**
     * @param \Consolidation\AnnotatedCommand\CommandData $commandData
     * @param string $interfaceName
     * @return object
     */
    public function get(CommandData $commandData, $interfaceName): ?object
    {
        switch ($interfaceName) {
            case 'AcquiaCli\Cli\CloudApi':
                return $this->cloudapi;
            case 'AcquiaCli\Cli\Config':
                return $this->config;
            case 'AcquiaCloudApi\Connector\Client':
                return $this->client;
            case 'AcquiaLogstream\LogstreamManager':
                return $this->logstream;
            case 'AcquiaCloudApi\Endpoints\Applications':
                return new Applications($this->client);
            case 'AcquiaCloudApi\Endpoints\Environments':
                return new Environments($this->client);
            case 'AcquiaCloudApi\Endpoints\Databases':
                return new Databases($this->client);
            case 'AcquiaCloudApi\Endpoints\Servers':
                return new Servers($this->client);
            case 'AcquiaCloudApi\Endpoints\Domains':
                return new Domains($this->client);
            case 'AcquiaCloudApi\Endpoints\Code':
                return new Code($this->client);
            case 'AcquiaCloudApi\Endpoints\DatabaseBackups':
                return new DatabaseBackups($this->client);
            case 'AcquiaCloudApi\Endpoints\Crons':
                return new Crons($this->client);
            case 'AcquiaCloudApi\Endpoints\Account':
                return new Account($this->client);
            case 'AcquiaCloudApi\Endpoints\Roles':
                return new Roles($this->client);
            case 'AcquiaCloudApi\Endpoints\Permissions':
                return new Permissions($this->client);
            case 'AcquiaCloudApi\Endpoints\Teams':
                return new Teams($this->client);
            case 'AcquiaCloudApi\Endpoints\Variables':
                return new Variables($this->client);
            case 'AcquiaCloudApi\Endpoints\Logs':
                return new Logs($this->client);
            case 'AcquiaCloudApi\Endpoints\Notifications':
                return new Notifications($this->client);
            case 'AcquiaCloudApi\Endpoints\Insights':
                return new Insights($this->client);
            case 'AcquiaCloudApi\Endpoints\LogForwardingDestinations':
                return new LogForwardingDestinations($this->client);
            case 'AcquiaCloudApi\Endpoints\SslCertificates':
                return new SslCertificates($this->client);
            case 'AcquiaCloudApi\Endpoints\Organizations':
                return new Organizations($this->client);
            case 'AcquiaCloudApi\Endpoints\Ides':
                return new Ides($this->client);
            default:
                return null;
        }
    }
}
