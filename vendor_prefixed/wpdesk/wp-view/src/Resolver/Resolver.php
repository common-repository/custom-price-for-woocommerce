<?php

namespace CPWFreeVendor\WPDesk\View\Resolver;

use CPWFreeVendor\WPDesk\View\Renderer\Renderer;
/**
 * Can resolve template name to a file
 */
interface Resolver
{
    /**
     * Resolve a template/pattern name to a resource the renderer can consume
     *
     * @param  string $name
     * @param  null|Resolver $renderer
     *
     * @return string
     */
    public function resolve($name, \CPWFreeVendor\WPDesk\View\Renderer\Renderer $renderer = null);
}
