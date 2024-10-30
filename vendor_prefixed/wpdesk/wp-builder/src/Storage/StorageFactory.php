<?php

namespace CPWFreeVendor\WPDesk\PluginBuilder\Storage;

class StorageFactory
{
    /**
     * @return PluginStorage
     */
    public function create_storage()
    {
        return new \CPWFreeVendor\WPDesk\PluginBuilder\Storage\WordpressFilterStorage();
    }
}
