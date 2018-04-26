<?php

namespace PluginExportFormatMotorradbekleidungNET;

use Plenty\Modules\DataExchange\Services\ExportPresetContainer;
use Plenty\Plugin\ServiceProvider;

/**
 * Class ExportFormatServiceProvider
 * @package PluginExportFormatMotorradbekleidungNET
 */
class ExportFormatServiceProvider extends ServiceProvider
{
    /**
     * Abstract function for registering the service provider.
     */
    public function register()
    {

    }

    /**
     * Adds the export format to the export container.
     *
     * @param ExportPresetContainer $container
     */
    public function boot(ExportPresetContainer $container)
    {
        $container->add(
            'MotorradbekleidungNET-Plugin',
            'PluginExportFormatMotorradbekleidungNET\ResultField\ExportFormatResultFields',
            'PluginExportFormatMotorradbekleidungNET\Generator\ExportFormatGenerator',
            '',
            true,
			true,
            'item'
        );
    }
}