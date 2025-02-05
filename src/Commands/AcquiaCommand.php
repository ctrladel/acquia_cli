<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\DatabaseBackups;
use AcquiaCloudApi\Endpoints\Databases;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Notifications;
use AcquiaCloudApi\Response\DatabaseResponse;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Response\OperationResponse;
use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Robo\Robo;
use Robo\Tasks;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class AcquiaCommand
 *
 * @package AcquiaCli\Commands
 */
abstract class AcquiaCommand extends Tasks
{
    /**
     * @var \AcquiaCli\Cli\CloudApi $cloudapiService
     */
    protected $cloudapiService;

    /**
     * @var \AcquiaCloudApi\Connector\Client $cloudapi
     */
    protected $cloudapi;

    /**
     * Regex for a valid UUID string.
     */
    public const UUIDV4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

    /**
     * Task response from API indicates failure.
     */
    protected const TASKFAILED = 'failed';

    /**
     * Task response from API indicates completion.
     */
    protected const TASKCOMPLETED = 'completed';

    /**
     * Task response from API indicates started.
     */
    protected const TASKSTARTED = 'started';

    /**
     * Task response from API indicates in progress.
     */
    protected const TASKINPROGRESS = 'in-progress';

    /**
     * AcquiaCommand constructor.
     */
    public function __construct()
    {
        $this->cloudapi = Robo::service('client');
        $this->cloudapiService = Robo::service('cloudApi');

        $this->setTableStyles();
    }

    /**
     * Override the confirm method from consolidation/Robo to allow automatic
     * confirmation.
     *
     * @param string $question
     * @param bool   $default
     */
    protected function confirm($question, $default = false)
    {
        if ($this->input()->getOption('yes')) {
            if ($this->output->isVerbose()) {
                $this->say('Ignoring confirmation question as --yes option passed.');
            }

            return 'true';
        }

        return parent::confirm($question, $default);
    }

    /**
     * Adds sort, limit, and filter options to the CloudAPI request.
     *
     * @hook validate
     *
     * @param CommandData $commandData
     */
    public function validateApiOptionsHook(CommandData $commandData): void
    {
        if ($limit = $commandData->input()->getOption('limit')) {
            $this->cloudapi->addQuery('limit', $limit);
        }
        if ($sort = $commandData->input()->getOption('sort')) {
            $this->cloudapi->addQuery('sort', $sort);
        }
        if ($filter = $commandData->input()->getOption('filter')) {
            $this->cloudapi->addQuery('filter', $filter);
        }
    }

    /**
     * Replace application names and environment names with UUIDs before
     * commands run.
     *
     * @hook init
     *
     * @param InputInterface $input
     * @param AnnotationData $annotationData
     * @throws \Exception
     */
    public function initUuidHook(InputInterface $input, AnnotationData $annotationData): void
    {
        if ($input->hasArgument('uuid')) {
            $uuid = $input->getArgument('uuid');

            // Detect if a UUID has been passed in or a sitename.
            if (is_string($uuid) && !preg_match(self::UUIDV4, $uuid)) {
                // Detect if this is not a fully qualified Acquia sitename e.g. prod:acquia
                if (strpos($uuid, ':') === false) {
                    // Use a realm passed in from the command line e.g. --realm=devcloud.
                    // If no realm is specified, 'prod:' will be prepended by default.
                    if ($input->hasOption('realm') && is_string($input->getOption('realm'))) {
                        $uuid = $input->getOption('realm') . ':' . $uuid;
                    }
                }
                $uuid = $this->cloudapiService->getApplicationUuid($uuid);
                $input->setArgument('uuid', $uuid);
            }
        }
    }

    /**
     * Waits for a notification to complete.
     *
     * @param \AcquiaCloudApi\Response\OperationResponse $response
     * @throws \Exception
     */
    protected function waitForNotification(OperationResponse $response): bool
    {
        if ($this->input()->getOption('no-wait')) {
            if ($this->output->isVerbose()) {
                $this->say('Skipping wait for notification.');
            }
            return true;
        }

        /** @phpstan-ignore-next-line */
        $notificationArray = explode('/', $response->links->notification->href);
        /** @phpstan-ignore-next-line */
        if (empty($notificationArray)) {
            throw new \Exception('Notification UUID not found.');
        }
        $notificationUuid = end($notificationArray);

        $extraConfig = Robo::config()->get('extraconfig');
        $sleep = $extraConfig['taskwait'];
        $timeout = $extraConfig['timeout'];

        $timezone = new \DateTimeZone('UTC');
        $start = new \DateTime(date('c'));
        $start->setTimezone($timezone);

        $progress = $this->getProgressBar();
        $progress->setFormat("<fg=white;bg=cyan> %message:-45s%</>\n%elapsed:6s% [%bar%] %percent:3s%%");
        $progress->setMessage('Looking up notification');
        $progress->start();

        $notificationAdapter = new Notifications($this->cloudapi);

        while (true) {
            $progress->advance($sleep);
            // Sleep initially to ensure that the task gets registered.
            sleep($sleep);

            $notification = $notificationAdapter->get($notificationUuid);

            $progress->setMessage(sprintf('Notification %s: %s', $notification->uuid, $notification->status));
            switch ($notification->status) {
                case self::TASKFAILED:
                    // If there's one failure we should throw an exception
                    throw new \Exception('Acquia task failed.');
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
     * Copy all DBs from one environment to another.
     *
     * @param string $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     * @param string|null $dbName The DB to move, if null move all DBs.
     * @param boolean $backup Whether to backup DBs first.
     * @throws \Exception
     */
    protected function moveDbs(string $uuid, EnvironmentResponse $environmentFrom, EnvironmentResponse $environmentTo, ?string $dbName = null, ?bool $backup = true): void
    {
        if (null !== $dbName) {
            $this->cloudapi->addQuery('filter', "name=${dbName}");
        }

        $dbAdapter = new Databases($this->cloudapi);
        $databases = $dbAdapter->getAll($uuid);
        $this->cloudapi->clearQuery();

        foreach ($databases as $database) {
            if ($backup) {
                $this->backupDb($uuid, $environmentTo, $database);
            }

            // Copy DB from prod to non-prod.
            $this->say(
                sprintf(
                    'Moving DB (%s) from %s to %s',
                    $database->name,
                    $environmentFrom->label,
                    $environmentTo->label
                )
            );

            $databaseAdapter = new Databases($this->cloudapi);
            $response = $databaseAdapter->copy($environmentFrom->uuid, $database->name, $environmentTo->uuid);
            $this->waitForNotification($response);
        }
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     */
    protected function backupAllEnvironmentDbs(string $uuid, EnvironmentResponse $environment): void
    {
        $dbAdapter = new Databases($this->cloudapi);
        $databases = $dbAdapter->getAll($uuid);
        foreach ($databases as $database) {
            $this->backupDb($uuid, $environment, $database);
        }
    }

    /**
     * @param string $uuid
     * @param EnvironmentResponse $environment
     * @param DatabaseResponse $database
     * @throws \Exception
     */
    protected function backupDb(string $uuid, EnvironmentResponse $environment, DatabaseResponse $database): void
    {
        // Run database backups.
        $this->say(sprintf('Backing up DB (%s) on %s', $database->name, $environment->label));
        $dbAdapter = new DatabaseBackups($this->cloudapi);
        $response = $dbAdapter->create($environment->uuid, $database->name);
        $this->waitForNotification($response);
    }

    /**
     * @param string $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     * @throws \Exception
     */
    protected function copyFiles(string $uuid, EnvironmentResponse $environmentFrom, EnvironmentResponse $environmentTo): void
    {
        $environmentsAdapter = new Environments($this->cloudapi);
        $this->say(sprintf('Copying files from %s to %s', $environmentFrom->label, $environmentTo->label));
        $response = $environmentsAdapter->copyFiles($environmentFrom->uuid, $environmentTo->uuid);
        $this->waitForNotification($response);
    }

    protected function setTableStyles(): void
    {
        $tableStyle = new TableStyle();
        $tableStyle->setPadType(STR_PAD_BOTH);
        Table::setStyleDefinition('center-align', $tableStyle);
    }

    protected function getProgressBar(): ProgressBar
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
