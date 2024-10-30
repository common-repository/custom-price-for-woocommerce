<?php

namespace CPWFreeVendor\WPDesk\View\Resolver;

use CPWFreeVendor\WPDesk\View\Renderer\Renderer;
use CPWFreeVendor\WPDesk\View\Resolver\Exception\CanNotResolve;
/**
 * This resolver never finds the file
 *
 * @package WPDesk\View\Resolver
 */
class NullResolver implements \CPWFreeVendor\WPDesk\View\Resolver\Resolver
{
    public function resolve($name, \CPWFreeVendor\WPDesk\View\Renderer\Renderer $renderer = null)
    {
        throw new \CPWFreeVendor\WPDesk\View\Resolver\Exception\CanNotResolve('Null Cannot resolve');
    }
}
