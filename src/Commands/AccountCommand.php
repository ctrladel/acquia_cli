<?php

namespace AcquiaCli\Commands;

use AcquiaCli\Cli\Config;
use AcquiaCloudApi\Endpoints\Account;

/**
 * Class AccountCommand
 *
 * @package AcquiaCli\Commands
 */
class AccountCommand extends AcquiaCommand
{
    /**
     * Gets information about the user's account.
     *
     * @command account
     */
    public function account(Config $config, Account $accountAdapter): void
    {

        $extraConfig = $config->get('extraconfig');
        $tz = $extraConfig['timezone'];
        $format = $extraConfig['format'];
        $timezone = new \DateTimeZone($tz);

        $account = $accountAdapter->get();

        $lastLogin = new \DateTime($account->last_login_at);
        $lastLogin->setTimezone($timezone);
        $createdAt = new \DateTime($account->created_at);
        $createdAt->setTimezone($timezone);

        $this->say(sprintf('Name: %s', $account->name));
        $this->say(sprintf('Last login: %s', $lastLogin->format($format)));
        $this->say(sprintf('Created at: %s', $createdAt->format($format)));
        /** @phpstan-ignore-next-line */
        $this->say(sprintf('Status: %s', $account->flags->active ? '✓' : ' '));
        /** @phpstan-ignore-next-line */
        $this->say(sprintf('TFA: %s', $account->flags->tfa ? '✓' : ' '));
    }
}
