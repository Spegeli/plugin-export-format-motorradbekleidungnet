<?php

namespace ElasticExportMotorradbekleidungNET;

use Plenty\Modules\DataExchange\Services\ExportPresetContainer;
use Plenty\Plugin\ServiceProvider;
use Plenty\Log\Services\ReferenceContainer;

/**
 * Class ElasticExportMotorradbekleidungNETServiceProvider
 * @package ElasticExportMotorradbekleidungNET
 */
class ElasticExportMotorradbekleidungNETServiceProvider extends ServiceProvider
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
            'ElasticExportMotorradbekleidungNET\ResultField\MotorradbekleidungNET',
            'ElasticExportMotorradbekleidungNET\Generator\MotorradbekleidungNET',
            '',
            true,
			true
        );
    }
}