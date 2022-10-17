<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\Crons;
use AcquiaCloudApi\Response\CronResponse;
use Symfony\Component\Console\Helper\Table;

/**
 * Class CronCommand
 *
 * @package AcquiaCli\Commands
 */
class CronCommand extends AcquiaCommand
{
    /**
     * Shows all cron tasks associated with an environment.
     *
     * @param string $uuid
     * @param string $environment
     *
     * @throws \Exception
     * @command cron:list
     */
    public function crons(Crons $cronAdapter, string $uuid, string $environment): void
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);

        $crons = $cronAdapter->getAll($environment->uuid);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['ID', 'Command', 'Frequency']);

        foreach ($crons as $cron) {
            $frequency = $this->convertCronFrequencyToCrontab($cron);

            $table
                ->addRows(
                    [
                    [
                        $cron->id,
                        $cron->command,
                        $frequency,
                    ],
                    ]
                );
        }

        $table->render();
        $this->say('Cron commands starting with "#" are disabled.');
    }

    /**
     * Adds a new cron task for an environment.
     *
     * @param string $uuid
     * @param string $environment
     * @param string $commandString The command to be run on cron wrapped in quotes.
     * @param string $frequency The crontab format frequency wrapped in quotes
     * @param string $label An optional label for the cron command wrapped in quotes.
     *
     * @throws \Exception
     * @command cron:create
     * @aliases cron:add
     */
    public function cronAdd(Crons $cronAdapter, string $uuid, string $environment, string $commandString, string $frequency, string $label): void
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        $this->say(sprintf('Adding new cron task on %s environment', $environment->name));
        $response = $cronAdapter->create($environment->uuid, $commandString, $frequency, $label);
        $this->waitForNotification($response);
    }

    /**
     * Removes a cron task for an environment.
     *
     * @param string $uuid
     * @param string $environment
     * @param int $cronId
     *
     * @throws \Exception
     * @command cron:delete
     * @aliases cron:remove
     */
    public function cronDelete(Crons $cronAdapter, string $uuid, string $environment, int $cronId): void
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        if ($this->confirm("Are you sure you want to delete the cron task?")) {
            $this->say(sprintf('Deleting cron task %s from %s', $cronId, $environment->label));
            $response = $cronAdapter->delete($environment->uuid, $cronId);
            $this->waitForNotification($response);
        }
    }

    /**
     * Enables a disabled cron entry.
     *
     * @param string $uuid
     * @param string $environment
     * @param int $cronId
     *
     * @throws \Exception
     * @command cron:enable
     */
    public function cronEnable(Crons $cronAdapter, string $uuid, string $environment, int $cronId): void
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        $this->say(sprintf('Enabling cron task %s on %s environment', $cronId, $environment->name));
        $response = $cronAdapter->enable($environment->uuid, $cronId);
        $this->waitForNotification($response);
    }

    /**
     * Disables an enabled cron entry.
     *
     * @param string $uuid
     * @param string $environment
     * @param int $cronId
     *
     * @throws \Exception
     * @command cron:disable
     */
    public function cronDisable(Crons $cronAdapter, string $uuid, string $environment, int $cronId): void
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        if ($this->confirm("Are you sure you want to disable the cron task?")) {
            $this->say(sprintf('Disabling cron task %s on %s environment', $cronId, $environment->name));
            $response = $cronAdapter->disable($environment->uuid, $cronId);
            $this->waitForNotification($response);
        }
    }

    /**
     * Shows detailed information about a single cron command.
     *
     * @param string $uuid
     * @param string $environment
     * @param int $cronId
     *
     * @throws \Exception
     * @command cron:info
     */
    public function cronInfo(Crons $cronAdapter, string $uuid, string $environment, int $cronId): void
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        $cron = $cronAdapter->get($environment->uuid, $cronId);
        /** @phpstan-ignore-next-line */
        $enabled = $cron->flags->enabled ? '✓' : ' ';
        /** @phpstan-ignore-next-line */
        $system = $cron->flags->system ? '✓' : ' ';
        /** @phpstan-ignore-next-line */
        $onAnyWeb = $cron->flags->on_any_web ? '✓' : ' ';

        $this->say(sprintf('ID: %s', $cron->id));
        $this->say(sprintf('Label: %s', $cron->label));
        /** @phpstan-ignore-next-line */
        $this->say(sprintf('Environment: %s', $cron->environment->name));
        $this->say(sprintf('Command: %s', $cron->command));
        $this->say(sprintf('Frequency: %s', $this->convertCronFrequencyToCrontab($cron)));
        $this->say(sprintf('Enabled: %s', $enabled));
        $this->say(sprintf('System: %s', $system));
        $this->say(sprintf('On any web: %s', $onAnyWeb));
    }

    protected function convertCronFrequencyToCrontab(CronResponse $cron): string
    {
        $frequency = [
            $cron->minute,
            $cron->hour,
            $cron->dayMonth,
            $cron->month,
            $cron->dayWeek,
        ];

        return implode(' ', $frequency);
    }
}
