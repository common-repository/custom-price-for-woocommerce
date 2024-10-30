<?php

namespace CPWFreeVendor\WPDesk\Composer\Codeception;

use CPWFreeVendor\Composer\Composer;
use CPWFreeVendor\Composer\IO\IOInterface;
use CPWFreeVendor\Composer\Plugin\Capable;
use CPWFreeVendor\Composer\Plugin\PluginInterface;
/**
 * Composer plugin.
 *
 * @package WPDesk\Composer\Codeception
 */
class Plugin implements \CPWFreeVendor\Composer\Plugin\PluginInterface, \CPWFreeVendor\Composer\Plugin\Capable
{
    /**
     * @var Composer
     */
    private $composer;
    /**
     * @var IOInterface
     */
    private $io;
    public function activate(\CPWFreeVendor\Composer\Composer $composer, \CPWFreeVendor\Composer\IO\IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }
    /**
     * @inheritDoc
     */
    public function deactivate(\CPWFreeVendor\Composer\Composer $composer, \CPWFreeVendor\Composer\IO\IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }
    /**
     * @inheritDoc
     */
    public function uninstall(\CPWFreeVendor\Composer\Composer $composer, \CPWFreeVendor\Composer\IO\IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }
    public function getCapabilities()
    {
        return [\CPWFreeVendor\Composer\Plugin\Capability\CommandProvider::class => \CPWFreeVendor\WPDesk\Composer\Codeception\CommandProvider::class];
    }
}
