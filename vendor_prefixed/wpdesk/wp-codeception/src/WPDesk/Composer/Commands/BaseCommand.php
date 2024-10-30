<?php

namespace CPWFreeVendor\WPDesk\Composer\Codeception\Commands;

use CPWFreeVendor\Composer\Command\BaseCommand as CodeceptionBaseCommand;
use CPWFreeVendor\Symfony\Component\Console\Output\OutputInterface;
/**
 * Base for commands - declares common methods.
 *
 * @package WPDesk\Composer\Codeception\Commands
 */
abstract class BaseCommand extends \CPWFreeVendor\Composer\Command\BaseCommand
{
    /**
     * @param string $command
     * @param OutputInterface $output
     */
    protected function execAndOutput($command, \CPWFreeVendor\Symfony\Component\Console\Output\OutputInterface $output)
    {
        \passthru($command);
    }
}
