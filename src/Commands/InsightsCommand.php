<?php

namespace AcquiaCli\Commands;

use AcquiaCli\Cli\CloudApi;
use AcquiaCloudApi\Endpoints\Insights;
use AcquiaCloudApi\Response\InsightCountResponse;
use AcquiaCloudApi\Response\InsightResponse;
use Symfony\Component\Console\Helper\Table;

/**
 * Class InsightsCommand
 *
 * @package AcquiaCli\Commands
 */
class InsightsCommand extends AcquiaCommand
{
    /**
     * Shows Insights information for specified applications.
     *
     * @param string $uuid
     * @param string|null $environment
     *
     * @throws \Exception
     * @command insights:info
     */
    public function insightsInfo(CloudApi $cloudapi, Insights $insightsAdapter, string $uuid, ?string $environment = null): void
    {

        if (null === $environment) {
            $insights = $insightsAdapter->getAll($uuid);
        } else {
            $environment = $cloudapi->getEnvironment($uuid, $environment);
            $insights = $insightsAdapter->getEnvironment($environment->uuid);
        }
        foreach ($insights as $insight) {
            /**
             * @var InsightResponse $insight
             */

            $this->renderInsightInfo($insight);
        }
    }

    /**
     * Shows insights alerts for specified applications.
     *
     * @param string $siteId
     * @option failed Whether to only show failed insight checks.
     *
     * @command insights:alerts:list
     */
    public function insightsAlertsList(Insights $insightsAdapter, string $siteId, array $options = ['failed' => null]): void
    {
        $alerts = $insightsAdapter->getAllAlerts($siteId);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['UUID', 'Description', 'Failed', 'Resolved', 'Ignored']);
        $table->setColumnStyle(2, 'center-align');
        $table->setColumnStyle(3, 'center-align');
        $table->setColumnStyle(4, 'center-align');

        foreach ($alerts as $alert) {
            if ($options['failed'] && !$alert->failed_value) {
                continue;
            }

            $table
                ->addRows(
                    [
                    [
                        $alert->uuid,
                        $alert->name,
                        $alert->failed_value ? '✓' : '',
                        /** @phpstan-ignore-next-line */
                        $alert->flags->resolved ? '✓' : '',
                        /** @phpstan-ignore-next-line */
                        $alert->flags->ignored ? '✓' : '',
                    ],
                    ]
                );
        }

        $table->render();
    }

    /**
     * Shows insights alerts for specified applications.
     *
     * @param string $siteId
     * @param string $alertUuid
     *
     * @command insights:alerts:get
     */
    public function insightsAlertsGet(Insights $insightsAdapter, string $siteId, string $alertUuid): void
    {
        $alert = $insightsAdapter->getAlert($siteId, $alertUuid);

        $this->say(sprintf('UUID: %s', $alert->uuid));
        $this->say(sprintf('Name: %s', $alert->name));
        $this->say(sprintf('Message: %s', filter_var($alert->message, FILTER_SANITIZE_STRING)));
    }

    /**
     * Shows insights alerts for specified applications.
     *
     * @param string $siteId
     * @option enabled Whether to only show enabled modules.
     * @option upgradeable Whether to only show modules that need an upgrade.
     *
     * @command insights:modules
     */
    public function insightsModules(
        Insights $insightsAdapter,
        string $siteId,
        array $options = ['enabled', 'upgradeable']
    ): void {
        $modules = $insightsAdapter->getModules($siteId);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Name', 'Version', 'Enabled', 'Upgradeable']);
        $table->setColumnStyle(2, 'center-align');
        $table->setColumnStyle(3, 'center-align');

        foreach ($modules as $module) {
            /** @phpstan-ignore-next-line */
            if ($options['enabled'] && !$module->flags->enabled) {
                continue;
            }
            if ($options['upgradeable'] && array_search('upgradeable', $module->tags, true) === false) {
                continue;
            }

            $table
                ->addRows(
                    [
                    [
                        $module->name,
                        $module->version,
                        /** @phpstan-ignore-next-line */
                        $module->flags->enabled ? '✓' : '',
                        array_search('upgradeable', $module->tags, true) !== false ? '✓' : '',
                    ],
                    ]
                );
        }

        $table->render();
    }

    /**
     * @param InsightResponse $insight
     */
    private function renderInsightInfo(InsightResponse $insight): void
    {
        $title = $insight->label . ' (' . $insight->hostname . ')';
        /** @phpstan-ignore-next-line */
        $score = $insight->scores->insight;

        if ($score >= 85) {
            $colour = 'green';
        } elseif ($score >= 65) {
            $colour = 'yellow';
        } else {
            $colour = 'red';
        }
        $char = $this->decorationCharacter(' ', '➜');
        $format = "${char}  <fg=white;bg=${colour};options=bold>%s</fg=white;bg=${colour};options=bold>";
        $this->formattedOutput("${title} Score: " . $score, 20, $format);

        $this->say(sprintf('Site ID: %s', $insight->uuid));
        $this->say(sprintf('Status: %s', $insight->status));

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Type', 'Pass', 'Fail', 'Ignored', 'Total', '%']);
        /** @phpstan-ignore-next-line */
        foreach ($insight->counts as $type => $count) {
            /**
             * @var InsightCountResponse $count
             */
            $table
                ->addRows(
                    [
                    [
                        ucwords(str_replace('_', ' ', $type)),
                        $count->pass,
                        $count->fail,
                        $count->ignored,
                        $count->total,
                        $count->percent,
                    ],
                    ]
                );
        }

        $table->render();
    }
}
