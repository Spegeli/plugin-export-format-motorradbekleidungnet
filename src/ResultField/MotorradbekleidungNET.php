<?php

namespace ElasticExportMotorradbekleidungNET\ResultField;

use Plenty\Modules\Cloud\ElasticSearch\Lib\ElasticSearch;
use Plenty\Modules\Cloud\ElasticSearch\Lib\Source\Mutator\BuiltIn\LanguageMutator;
use Plenty\Modules\DataExchange\Contracts\ResultFields;
use Plenty\Modules\DataExchange\Models\FormatSetting;
use ElasticExport\DataProvider\ResultFieldDataProvider;
use Plenty\Modules\Helper\Services\ArrayHelper;
use Plenty\Modules\Item\Search\Mutators\SkuMutator;
use Plenty\Modules\Item\Search\Mutators\KeyMutator;
use Plenty\Modules\Item\Search\Mutators\DefaultCategoryMutator;
use Plenty\Modules\Item\Search\Mutators\BarcodeMutator;
use Plenty\Modules\Item\Search\Mutators\ImageMutator;
use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\Log\Loggable;

/**
 * Class MotorradbekleidungNET
 * @package ElasticExportMotorradbekleidungNET\ResultField
 */
class MotorradbekleidungNET extends ResultFields
{
	use Loggable;
	
    /**
     * @var ArrayHelper
     */
    private $arrayHelper;

	/**
	 * @var ConfigRepository
	 */
	private $configRepository;		
	
    /**
     * MotorradbekleidungNET constructor.
     * @param ArrayHelper $arrayHelper
	 * @param ConfigRepository $configRepository	 
     */
    public function __construct(
        ArrayHelper $arrayHelper, 
		ConfigRepository $configRepository
    )
    {
        $this->arrayHelper = $arrayHelper;
		$this->configRepository = $configRepository;
    }

    /**
     * Generate result fields.
     *
     * @param  array $formatSettings = []
     * @return array
     */
    public function generateResultFields(array $formatSettings = []):array
    {
        $settings = $this->arrayHelper->buildMapFromObjectList($formatSettings, 'key', 'value');

		$marketID = (float)$this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.set_marketid');
        $reference = $settings->get('referrerId') ? $settings->get('referrerId') : $marketID;		
		
		$this->setOrderByList(['path' => 'variation.itemId', 'order' => ElasticSearch::SORTING_ORDER_ASC]);

        //Mutator
        /**
         * @var ImageMutator $imageMutator
         */
        $imageMutator = pluginApp(ImageMutator::class);
        if($imageMutator instanceof ImageMutator)
        {
			// add image reference for a specific market
            $imageMutator->addMarket($reference);		
        }

        /**
         * @var KeyMutator $keyMutator
         */
        $keyMutator = pluginApp(KeyMutator::class);
        if($keyMutator instanceof KeyMutator)
        {
            $keyMutator->setKeyList($this->getKeyList());
            $keyMutator->setNestedKeyList($this->getNestedKeyList());
        }

        /**
         * @var LanguageMutator $languageMutator
         */
        $languageMutator = pluginApp(LanguageMutator::class, ['languages' => [$settings->get('lang')]]);
		
        /**
         * @var SkuMutator $skuMutator
         */
        $skuMutator = pluginApp(SkuMutator::class);
        if($skuMutator instanceof SkuMutator)
        {
            $skuMutator->setMarket($reference);
        }

        /**
         * @var DefaultCategoryMutator $defaultCategoryMutator
         */
        $defaultCategoryMutator = pluginApp(DefaultCategoryMutator::class);
        if($defaultCategoryMutator instanceof DefaultCategoryMutator)
        {
            $defaultCategoryMutator->setPlentyId($settings->get('plentyId'));
        }

        /**
         * @var BarcodeMutator $barcodeMutator
         */
        $barcodeMutator = pluginApp(BarcodeMutator::class);
        if($barcodeMutator instanceof BarcodeMutator)
        {
            $barcodeMutator->addMarket($reference);
        }

		$resultFieldHelper = pluginApp(ResultFieldDataProvider::class);
		if($resultFieldHelper instanceof ResultFieldDataProvider)
		{
			$resultFields = $resultFieldHelper->getResultFields($settings);
		}

		if(isset($resultFields) && is_array($resultFields) && count($resultFields))
		{
			$fields[0] = $resultFields;
			$fields[1] = [
				$languageMutator,
				$skuMutator,
				$defaultCategoryMutator,
				$barcodeMutator,
				$keyMutator,
			];

			if($reference != -1)
			{
				$fields[1][] = $imageMutator;
			}
		}
		else
		{
			$this->getLogger(__METHOD__)->critical('ElasticExportMotorradbekleidungNET::log.resultFieldError');
			exit();
		}

        return $fields;
    }

    /**
     * Returns the list of keys.
     *
     * @return array
     */
    private function getKeyList()
    {
        $keyList = [
            //item
            'item.id',
            'item.manufacturer.id',

            //variation
            'variation.availability.id',
            'variation.model',
            'variation.isMain',
			'variation.availableUntil',
			'variation.updatedAt',

            //unit
            'unit.content',
            'unit.id',
        ];

        return $keyList;
    }

    /**
     * Returns the list of nested keys.
     *
     * @return mixed
     */
    private function getNestedKeyList()
    {
        $nestedKeyList['keys'] = [
            //images
            'images.all',
            'images.item',
            'images.variation',

            //sku
            'skus',

            //texts
            'texts',

            //defaultCategories
            'defaultCategories',

            //barcodes
            'barcodes',

            //attributes
            'attributes',

            //properties
            'properties',
        ];

        $nestedKeyList['nestedKeys'] = [
            //images
            'images.all' => [
                'urlMiddle',
                'urlPreview',
                'urlSecondPreview',
                'url',
                'path',
                'position',
            ],

            'images.item' => [
                'urlMiddle',
                'urlPreview',
                'urlSecondPreview',
                'url',
                'path',
                'position',
            ],

            'images.variation' => [
                'urlMiddle',
                'urlPreview',
                'urlSecondPreview',
                'url',
                'path',
                'position',
            ],

            //sku
            'skus' => [
                'sku',
            ],

            //texts
            'texts' => [
                'urlPath',
                'lang',
                'name1',
                'name2',
                'name3',
                'shortDescription',
                'description',
                'technicalData',
            ],

            //defaultCategories
            'defaultCategories' => [
                'id',
            ],

            //barcodes
            'barcodes' => [
                'code',
                'type',
            ],

            //attributes
            'attributes' => [
                'attributeValueSetId',
                'attributeId',
                'valueId',
                'names.name',
                'names.lang',
            ],

            //proprieties
            'properties' => [
                'property.id',
                'property.valueType',
                'selection.name',
                'selection.lang',
                'texts.value',
                'texts.lang',
                'valueInt',
                'valueFloat',
            ],
        ];

        return $nestedKeyList;
    }
}