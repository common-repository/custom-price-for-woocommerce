<?php

namespace CPWFreeVendor\WPDesk\Composer\Codeception;

use CPWFreeVendor\WPDesk\Composer\Codeception\Commands\CreateCodeceptionTests;
use CPWFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareCodeceptionDb;
use CPWFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareLocalCodeceptionTests;
use CPWFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareLocalCodeceptionTestsWithCoverage;
use CPWFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareParallelCodeceptionTests;
use CPWFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareWordpressForCodeception;
use CPWFreeVendor\WPDesk\Composer\Codeception\Commands\RunCodeceptionTests;
use CPWFreeVendor\WPDesk\Composer\Codeception\Commands\RunLocalCodeceptionTests;
use CPWFreeVendor\WPDesk\Composer\Codeception\Commands\RunLocalCodeceptionTestsWithCoverage;
/**
 * Links plugin commands handlers to composer.
 */
class CommandProvider implements \CPWFreeVendor\Composer\Plugin\Capability\CommandProvider
{
    public function getCommands()
    {
        return [new \CPWFreeVendor\WPDesk\Composer\Codeception\Commands\CreateCodeceptionTests(), new \CPWFreeVendor\WPDesk\Composer\Codeception\Commands\RunCodeceptionTests(), new \CPWFreeVendor\WPDesk\Composer\Codeception\Commands\RunLocalCodeceptionTests(), new \CPWFreeVendor\WPDesk\Composer\Codeception\Commands\RunLocalCodeceptionTestsWithCoverage(), new \CPWFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareCodeceptionDb(), new \CPWFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareWordpressForCodeception(), new \CPWFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareLocalCodeceptionTests(), new \CPWFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareLocalCodeceptionTestsWithCoverage(), new \CPWFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareParallelCodeceptionTests()];
    }
}
