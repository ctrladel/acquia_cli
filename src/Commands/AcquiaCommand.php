<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Organizations;
use AcquiaCloudApi\Endpoints\Notifications;
use AcquiaCloudApi\Endpoints\Databases;
use AcquiaCloudApi\Endpoints\DatabaseBackups;
use AcquiaCloudApi\Response\DatabaseResponse;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Response\OperationResponse;
use AcquiaCloudApi\Response\OrganizationResponse;
use Consolidation\AnnotatedCommand\CommandData;
use Robo\Tasks;
use Robo\Robo;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;


/**
 * Class AcquiaCommand
 * @package AcquiaCli\Commands
 */
abstract class AcquiaCommand extends Tasks
{
    // @TODO https://github.com/boedah/robo-drush/issues/18
    //use \Boedah\Robo\Task\Drush\loadTasks;

    protected $cloudapiService;

    /** Regex for a valid UUID string. */
    const UUIDV4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

    /** Task response from API indicates failure. */
    const TASKFAILED = 'failed';

    /** Task response from API indicates completion. */
    const TASKCOMPLETED = 'completed';

    /** Task response from API indicates started. */
    const TASKSTARTED = 'started';

    /** Task response from API indicates in progress. */
    const TASKINPROGRESS = 'in-progress';

    /**
     * AcquiaCommand constructor.
     */
    public function __construct()
    {

        $cloudapiService = Robo::service('cloudApi');
        $this->cloudapiService = $cloudapiService;
        $this->cloudapi = $cloudapiService->getClient();

        $this->setTableStyles();
    }

    public function getCloudApi()
    {
        $cloudapi = Robo::service('cloudApi')->getCloudApi();
        return $cloudapi;
    }

    public function getEnvironments()
    {
        $environments = Robo::service('cloudApi')->getEnvironments();
        return $environments;
    }

    public function getApplications()
    {
        $applications = Robo::service('cloudApi')->getApplications();
        return $applications;
    }

    /**
     * Override the confirm method from consolidation/Robo to allow automatic
     * confirmation.
     *
     * @param string $question
     * @param bool $default
     */
    protected function confirm($question, $default = false)
    {
        if ($this->input()->getOption('yes')) {
            // @TODO add this back in later.
            // $this->say('Ignoring confirmation question as --yes option passed.');

            return true;
        }

        return parent::confirm($question, $default);
    }

    /**
     * Replace application names and environment names with UUIDs before
     * commands run.
     *
     * @hook validate
     *
     * @param CommandData $commandData
     */
    public function validateUuidHook(CommandData $commandData)
    {
        if ($commandData->input()->hasArgument('uuid')) {
            $uuid = $commandData->input()->getArgument('uuid');

            // Detect if a UUID has been passed in or a sitename.
            if (!preg_match(self::UUIDV4, $uuid)) {
                // Detect if this is not a fully qualified Acquia sitename e.g. prod:acquia
                if (strpos($uuid, ':') === false) {
                    // Use a realm passed in from the command line e.g. --realm=devcloud.
                    // If no realm is specified, 'prod:' will be prepended by default.
                    if ($commandData->input()->hasOption('realm')) {
                        $uuid = $commandData->input()->getOption('realm') . ':' . $uuid;
                    }
                }
                $uuid = $this->getUuidFromHostingName($uuid);
                $commandData->input()->setArgument('uuid', $uuid);
            }

            // Convert environment parameters to an EnvironmentResponse
            // if ($commandData->input()->hasArgument('environment')) {
            //     $environmentName = $commandData->input()->getArgument('environment');
            //     if (null !== $environmentName) {
            //         $environment = $this->getEnvironmentFromEnvironmentName($uuid, $environmentName);
            //         $commandData->input()->setArgument('environment', $environment);
            //     }
            // }
            // if ($commandData->input()->hasArgument('environmentFrom')) {
            //     $environmentFromName = $commandData->input()->getArgument('environmentFrom');
            //     $environmentFrom = $this->getEnvironmentFromEnvironmentName($uuid, $environmentFromName);
            //     $commandData->input()->setArgument('environmentFrom', $environmentFrom);
            // }
            // if ($commandData->input()->hasArgument('environmentTo')) {
            //     $environmentToName = $commandData->input()->getArgument('environmentTo');
            //     $environmentTo = $this->getEnvironmentFromEnvironmentName($uuid, $environmentToName);
            //     $commandData->input()->setArgument('environmentTo', $environmentTo);
            // }
        }
        // Convert Organization name to UUID.
        if ($commandData->input()->hasArgument('organization')) {
            $organizationName = $commandData->input()->getArgument('organization');
            $organization = $this->getOrganizationFromOrganizationName($organizationName);
            $commandData->input()->setArgument('organization', $organization);
        }
    }

    /**
     * @param string $uuid
     * @param string $environment
     * @return EnvironmentResponse
     * @throws Exception
     */
    // protected function getEnvironmentFromEnvironmentName($uuid, $environment)
    // {
    //     $environmentsAdapter = new Environments($this->cloudapi);
    //     $environments = $environmentsAdapter->getAll($uuid);

    //     foreach ($environments as $e) {
    //         if ($environment === $e->name) {
    //             return $e;
    //         }
    //     }

    //     throw new Exception('Unable to find ID for environment');
    // }

    /**
     * @param string $organizationName
     * @return OrganizationResponse
     * @throws Exception
     */
    protected function getOrganizationFromOrganizationName($organizationName)
    {
        $org = new Organizations($this->getCloudApi());
        $organizations = $org->getAll();

        foreach ($organizations as $organization) {
            if ($organizationName === $organization->name) {
                return $organization;
            }
        }

        throw new Exception('Unable to find ID for environment');
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    protected function getUuidFromHostingName($name)
    {
        $app = $this->getApplications();
        $applications = $app->getAll();

        foreach ($applications as $application) {
            if ($name === $application->hosting->id) {
                return $application->uuid;
            }
        }
        throw new Exception('Unable to find UUID for application');
    }

    /**
     * Waits for a notification to complete.
     *
     * @param OperationResponse $response
     * @throws \Exception
     */
    protected function waitForNotification($response)
    {
        if ($this->input()->getOption('no-wait')) {
            // @TODO put this back in later.
            // $this->say('Skipping wait for notification.');
            return true;
        }

        $notificationArray = explode('/', $response->links->notification->href);
        if (empty($notificationArray)) {
            throw new \Exception('Notification UUID not found.');
        }
        $notificationUuid = end($notificationArray);

        $extraConfig = $this->cloudapiService->getExtraConfig();
        $sleep = $extraConfig['taskwait'];
        $timeout = $extraConfig['timeout'];

        $timezone = new \DateTimeZone('UTC');
        $start = new \DateTime(date('c'));
        $start->setTimezone($timezone);

        $progress = $this->getProgressBar();
        $progress->setFormat("<fg=white;bg=cyan> %message:-45s%</>\n%elapsed:6s% [%bar%] %percent:3s%%");
        $progress->setMessage('Looking up notification');
        $progress->start();

        $notificationAdapter = new Notifications($this->getCloudApi());

        while (true) {
            $progress->advance($sleep);
            // Sleep initially to ensure that the task gets registered.
            sleep($sleep);

            $notification = $notificationAdapter->get($notificationUuid);

            $progress->setMessage('Notification ' . $notification->status);
            switch ($notification->status) {
                case self::TASKFAILED:
                    // If there's one failure we should throw an exception
                    throw new \Exception('Acquia task failed.');
                    break(2);
                // If tasks are started or in progress, we should continue back
                // to the top of the loop and wait until tasks are complete.
                case self::TASKSTARTED:
                case self::TASKINPROGRESS:
                    break;
                case self::TASKCOMPLETED:
                    // Completed tasks should break out of the loop and continue execution.
                    break(2);
                default:
                    throw new \Exception('Unknown notification status.');
                    break(2);
            }

            // Timeout if the command exceeds the configured timeout threshold.
            // Create a new DateTime for now.
            $current = new \DateTime(date('c'));
            $current->setTimezone($timezone);
            // Take our current time, remove our start time and see if it exceeds the timeout.
            if ($timeout <= ($current->getTimestamp() - $start->getTimestamp())) {
                throw new \Exception("Task timeout of ${timeout} seconds exceeded.");
            }
        }
        $progress->finish();
        $this->writeln(PHP_EOL);

        return true;
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     */
    protected function backupAndMoveDbs($uuid, $environmentFrom, $environmentTo, $dbName = null)
    {
<<<<<<< HEAD
        $dbAdapter = new Databases($this->getCloudApi());
=======
        if (null !== $dbName) {
            $this->cloudapi->addQuery('filter', "name=${dbName}");
        }

        $dbAdapter = new Databases($this->cloudapi);
>>>>>>> Various updates to the code and more tests.
        $databases = $dbAdapter->getAll($uuid);
        $this->cloudapi->clearQuery();

        foreach ($databases as $database) {
            $this->backupDb($uuid, $environmentTo, $database);

            // Copy DB from prod to non-prod.
            $this->say(
                sprintf(
                    'Moving DB (%s) from %s to %s',
                    $database->name,
                    $environmentFrom->label,
                    $environmentTo->label
                )
            );

            $databaseAdapter = new Databases($this->getCloudApi());
            $response = $databaseAdapter->copy($environmentFrom->uuid, $database->name, $environmentTo->uuid);
            $this->waitForNotification($response);
        }
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     */
    protected function backupAllEnvironmentDbs($uuid, $environment)
    {
<<<<<<< HEAD
        $dbAdapter = new Databases($this->getCloudApi());
=======
        $dbAdapter = new Databases($this->cloudapi);
        // var_dump($dbAdapter);
>>>>>>> Various updates to the code and more tests.
        $databases = $dbAdapter->getAll($uuid);
        // var_dump($databases);
        foreach ($databases as $database) {
            $this->backupDb($uuid, $environment, $database);
        }
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param DatabaseResponse    $database
     */
    protected function backupDb($uuid, $environment, $database)
    {
        // var_dump($database);
        // Run database backups.
        $this->say(sprintf('Backing up DB (%s) on %s', $database->name, $environment->label));
        $dbAdapter = new DatabaseBackups($this->getCloudApi());
        $response = $dbAdapter->create($environment->uuid, $database->name);
        $this->waitForNotification($response);
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     */
    protected function copyFiles($uuid, $environmentFrom, $environmentTo)
    {
        $environmentsAdapter = new Environments($this->cloudapi);
        $this->say(sprintf('Copying files from %s to %s', $environmentFrom->label, $environmentTo->label));
        $response = $environmentsAdapter->copyFiles($environmentFrom->uuid, $environmentTo->uuid);
        $this->waitForNotification($response);
    }

    protected function setTableStyles()
    {
        $tableStyle = new TableStyle();
        $tableStyle->setPadType(STR_PAD_BOTH);
        Table::setStyleDefinition('center-align', $tableStyle);
    }

    protected function getProgressBar()
    {
        // Kindly stolen from https://jonczyk.me/2017/09/20/make-cool-progressbar-symfony-command/
        $output = $this->output();
        $progressBar = new ProgressBar($output);
        $progressBar->setBarCharacter('<fg=green>⚬</>');
        $progressBar->setEmptyBarCharacter('<fg=red>⚬</>');
        $progressBar->setProgressCharacter('<fg=green>➤</>');

        return $progressBar;
    }
}
