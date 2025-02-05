<?php

namespace AcquiaCli\Commands;

use AcquiaCli\Cli\CloudApi;
use AcquiaCloudApi\Endpoints\Logs;
use AcquiaLogstream\LogstreamManager;
use Robo\Common\InputAwareTrait;
use Robo\Common\OutputAwareTrait;
use Symfony\Component\Console\Helper\Table;

/**
 * Class LogsCommand
 *
 * @package AcquiaCli\Commands
 */
class LogsCommand extends AcquiaCommand
{
    use InputAwareTrait;
    use OutputAwareTrait;

    /**
     * Streams logs from an environment.
     *
     * @param string $uuid
     * @param string $environment
     * @phpstan-param array<mixed> $opts
     * @option $colourise Colourise the output
     * @option $logtypes  Filter to specific log types
     * @option $servers   Filter to specific servers
     *
     * @command log:stream
     */
    public function logStream(
        CloudApi $cloudapi,
        LogstreamManager $logstream,
        Logs $logsAdapter,
        string $uuid,
        string $environment,
        array $opts = [
            'colourise|c' => false,
            'logtypes|t' => [],
            'servers' => []
        ]
    ): void {

        $environmentResponse = $cloudapi->getEnvironment($uuid, $environment);
        $stream = $logsAdapter->stream($environmentResponse->uuid);
        /** @phpstan-ignore-next-line */
        $logstream->setParams($stream->logstream->params);
        if ($opts['colourise']) {
            $logstream->setColourise(true);
        }
        $logstream->setLogTypeFilter($opts['logtypes']);
        $logstream->setLogServerFilter($opts['servers']);
        $logstream->stream();
    }

    /**
     * Shows available log files.
     *
     * @param string $uuid
     * @param string $environment
     *
     * @command log:list
     */
    public function logList(CloudApi $cloudapi, Logs $logsAdapter, string $uuid, string $environment): void
    {
        $environmentResponse = $cloudapi->getEnvironment($uuid, $environment);
        $logs = $logsAdapter->getAll($environmentResponse->uuid);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Type', 'Label', 'Available']);
        $table->setColumnStyle(2, 'center-align');
        foreach ($logs as $log) {
            $table
                ->addRows(
                    [
                    [
                        $log->type,
                        $log->label,
                        /** @phpstan-ignore-next-line */
                        $log->flags->available ? '✓' : ' ',
                    ],
                    ]
                );
        }
        $table->render();
    }

    /**
     * Creates a log snapshot.
     *
     * @param string $uuid
     * @param string $environment
     * @param string $logType
     *
     * @command log:snapshot
     */
    public function logSnapshot(CloudApi $cloudapi, Logs $logsAdapter, string $uuid, string $environment, string $logType): void
    {
        $environment = $cloudapi->getEnvironment($uuid, $environment);
        $this->say(sprintf('Creating snapshot for %s in %s environment', $logType, $environment->label));
        $logsAdapter->snapshot($environment->uuid, $logType);
    }

    /**
     * Downloads a log file.
     *
     * @param string $uuid
     * @param string $environment
     * @param string $logType
     * @phpstan-param array<mixed> $opts

     * @throws \Exception
     *
     * @command log:download
     * @option  $path Select a path to download the log to. If omitted, the system temp directory will be used.
     * @option  $filename Choose a filename for the dowloaded log. If omitted, the name will be automatically generated.
     */
    public function logDownload(
        CloudApi $cloudapi,
        Logs $logsAdapter,
        string $uuid,
        string $environment,
        string $logType,
        array $opts = ['path' => null, 'filename' => null]
    ): void {
        $environment = $cloudapi->getEnvironment($uuid, $environment);
        $log = $logsAdapter->download($environment->uuid, $logType);

        if (null === $opts['filename']) {
            $backupName = sprintf('%s-%s', $environment->name, $logType);
        } else {
            $backupName = $opts['filename'];
        }

        if (null === $opts['path']) {
            $tmpLocation = tempnam(sys_get_temp_dir(), $backupName);
            $location = sprintf('%s.tar.gz', $tmpLocation);
            if (is_string($tmpLocation)) {
                rename($tmpLocation, $location);
            } else {
                throw new \Exception('Unable to make temporary file.');
            }
        } else {
            $location = sprintf("%s%s%s.tar.gz", $opts['path'], \DIRECTORY_SEPARATOR, $backupName);
        }

        if (file_put_contents($location, $log, LOCK_EX)) {
            $this->say(sprintf('Log downloaded to %s', $location));
        } else {
            $this->say('Unable to download log.');
        }
    }
}
