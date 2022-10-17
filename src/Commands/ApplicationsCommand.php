<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Databases;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Response\EnvironmentResponse;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ApplicationsCommand
 *
 * @package AcquiaCli\Commands
 */
class ApplicationsCommand extends AcquiaCommand
{
    /**
     * Shows all sites a user has access to.
     *
     * @command application:list
     * @aliases app:list,a:l
     */
    public function applications(Applications $applicationsAdapter): void
    {

        $applications = $applicationsAdapter->getAll();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Name', 'UUID', 'Hosting ID']);
        foreach ($applications as $application) {
            $table
                ->addRows(
                    [
                        [
                            $application->name,
                            $application->uuid,
                            /** @phpstan-ignore-next-line */
                            $application->hosting->id,
                        ],
                    ]
                );
        }
        $table->render();
    }

    /**
     * Shows detailed information about a site.
     *
     * @param string $uuid
     *
     * @command application:info
     * @aliases app:info,a:i
     */
    public function applicationInfo(
        OutputInterface $output,
        Environments $environmentsAdapter,
        Databases $databasesAdapter,
        string $uuid
    ): void {
        $environments = $environmentsAdapter->getAll($uuid);

        $table = new Table($output);
        $table->setHeaders(['Environment', 'ID', 'Branch/Tag', 'Domain(s)', 'Database(s)']);

        $databases = $databasesAdapter->getAll($uuid);

        $dbNames = array_map(
            function ($database) {
                return $database->name;
            },
            $databases->getArrayCopy()
        );

        foreach ($environments as $environment) {
            /**
             * @var EnvironmentResponse $environment
             */

            $environmentName = sprintf('%s (%s)', $environment->label, $environment->name);
            /** @phpstan-ignore-next-line */
            if ($environment->flags->livedev) {
                $environmentName = sprintf('💻  %s', $environmentName);
            }

            /** @phpstan-ignore-next-line */
            if ($environment->flags->production_mode) {
                $environmentName = sprintf('🔒  %s', $environmentName);
            }

            $table
                ->addRows(
                    [
                        [
                            $environmentName,
                            $environment->uuid,
                            /** @phpstan-ignore-next-line */
                            $environment->vcs->path,
                            implode("\n", $environment->domains),
                            implode("\n", $dbNames)
                        ],
                    ]
                );
        }
        $table->render();

        if (isset($environment->vcs->url)) {
            $this->say(sprintf('🔧  Git URL: %s', $environment->vcs->url));
        }
        $this->say('💻  indicates environment in livedev mode.');
        $this->say('🔒  indicates environment in production mode.');
    }

    /**
     * Shows a list of all tags on an application.
     *
     * @param string $uuid
     *
     * @command application:tags
     * @aliases app:tags
     */
    public function applicationsTags(OutputInterface $output, Applications $applicationsAdapter, string $uuid): void
    {
        $tags = $applicationsAdapter->getAllTags($uuid);

        $table = new Table($output);
        $table->setHeaders(['Name', 'Color']);
        foreach ($tags as $tag) {
            $table
                ->addRows(
                    [
                        [
                            $tag->name,
                            $tag->color,
                        ],
                    ]
                );
        }
        $table->render();
    }

    /**
     * Creates an application tag.
     *
     * @param string $uuid
     * @param string $name
     * @param string $color
     *
     * @throws \Exception
     * @command application:tag:create
     * @aliases app:tag:create
     */
    public function applicationTagCreate(Applications $applicationsAdapter, string $uuid, string $name, string $color): void
    {
        $this->say(sprintf('Creating application tag %s:%s', $name, $color));
        $response = $applicationsAdapter->createTag($uuid, $name, $color);
        $this->waitForNotification($response);
    }

    /**
     * Deletes an application tag.
     *
     * @param string $uuid
     * @param string $name
     *
     * @throws \Exception
     * @command application:tag:delete
     * @aliases app:tag:delete
     */
    public function applicationTagDelete(Applications $applicationsAdapter, string $uuid, string $name): void
    {
        $this->say(sprintf('Deleting application tag %s', $name));
        $response = $applicationsAdapter->deleteTag($uuid, $name);
        $this->waitForNotification($response);
    }

    /**
     * Renames an application.
     *
     * @param string $uuid
     * @param string $name
     *
     * @command application:rename
     * @aliases app:rename,a:rename
     */
    public function applicationRename(Applications $applicationsAdapter, string $uuid, string $name): void
    {
        $this->say(sprintf('Renaming application to %s', $name));
        $environments = $applicationsAdapter->rename($uuid, $name);
    }
}
