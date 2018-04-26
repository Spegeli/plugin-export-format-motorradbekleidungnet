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
     * Function definition for registering the service provider.
     */
    public function register()
    {

    }

	/**
	 * @param ExportPresetContainer $exportPresetContainer
	 */
	public function boot(ExportPresetContainer $exportPresetContainer)
	{

		//Adds the export format to the export container.
		$exportPresetContainer->add(
			'MotorradbekleidungNET-Plugin',
			'ElasticExportMotorradbekleidungNET\ResultField\MotorradbekleidungNET',
			'ElasticExportMotorradbekleidungNET\Generator\MotorradbekleidungNET',
			'',
			true,
			true
		);
	}

}