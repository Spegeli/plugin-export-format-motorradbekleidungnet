<?php

namespace ElasticExportMotorradbekleidungNET\ResultField;

use Plenty\Modules\Cloud\ElasticSearch\Lib\ElasticSearch;
use Plenty\Modules\Cloud\ElasticSearch\Lib\Source\Mutator\BuiltIn\LanguageMutator;
use Plenty\Modules\DataExchange\Contracts\ResultFields;
use Plenty\Modules\DataExchange\Models\FormatSetting;
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
		
		$this->setOrderByList([
			'path'  => 'item.id',
			'order' => ElasticSearch::SORTING_ORDER_ASC]);
		
        $itemDescriptionFields = ['texts.urlPath', 'texts.lang'];
		
        $itemDescriptionFields[] = ($settings->get('nameId')) ? 'texts.name' . $settings->get('nameId') : 'texts.name1';

        if($settings->get('descriptionType') == 'itemShortDescription'
            || $settings->get('previewTextType') == 'itemShortDescription')
        {
            $itemDescriptionFields[] = 'texts.shortDescription';
        }

        if($settings->get('descriptionType') == 'itemDescription'
            || $settings->get('descriptionType') == 'itemDescriptionAndTechnicalData'
            || $settings->get('previewTextType') == 'itemDescription'
            || $settings->get('previewTextType') == 'itemDescriptionAndTechnicalData')
        {
            $itemDescriptionFields[] = 'texts.description';
        }
		
        if($settings->get('descriptionType') == 'technicalData'
            || $settings->get('descriptionType') == 'itemDescriptionAndTechnicalData'
            || $settings->get('previewTextType') == 'technicalData'
            || $settings->get('previewTextType') == 'itemDescriptionAndTechnicalData')
        {
            $itemDescriptionFields[] = 'texts.technicalData';
        }	
		
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

		// Fields
        $fields = [
            [
                //item
                'item.id',
                'item.manufacturer.id',			

                //variation
                'id',
                'variation.availability.id',
                'variation.model',
                'variation.isMain',
				'variation.weightG',
                'variation.releasedAt',				
                'variation.availableUntil',
				'variation.updatedAt',

                //unit
                'unit.content',
                'unit.id',				
				
                //images
                'images.all.urlMiddle',
                'images.all.urlPreview',
                'images.all.urlSecondPreview',
                'images.all.url',
                'images.all.path',
                'images.all.position',

                'images.item.urlMiddle',
                'images.item.urlPreview',
                'images.item.urlSecondPreview',
                'images.item.url',
                'images.item.path',
                'images.item.position',

                'images.variation.urlMiddle',
                'images.variation.urlPreview',
                'images.variation.urlSecondPreview',
                'images.variation.url',
                'images.variation.path',
                'images.variation.position',

                //sku
                'skus.sku',

                //texts
                'texts.urlPath',
				'texts.lang',
				'texts.name'.$settings->get('nameId'),
				'texts.shortDescription',
				'texts.description',	
				'texts.technicalData',					
				
                //defaultCategories
                'defaultCategories.id',

                //barcodes
                'barcodes.code',
                'barcodes.type',	

                //attributes
                'attributes.attributeValueSetId',
                'attributes.attributeId',
                'attributes.valueId',
				'attributes.names.name',
				'attributes.names.lang',
				
                //proprieties
                'properties.property.id',
                'properties.property.valueType',
                'properties.selection.name',
				'properties.selection.lang',
				'properties.texts.value',
				'properties.texts.lang',
				'properties.valueInt',
				'properties.valueFloat',
            ],
            [
			    //mutators
                $languageMutator,
                $skuMutator,
                $keyMutator,
                $defaultCategoryMutator,
                $barcodeMutator,
            ],
        ];

        // Get the associated images if reference is selected
        if($reference != -1)
        {
            $fields[1][] = $imageMutator;
        }

		if (is_array($itemDescriptionFields) && count($itemDescriptionFields) > 0)
        {
            foreach($itemDescriptionFields as $itemDescriptionField)
            {
                $fields[0][] = $itemDescriptionField;
            }
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
			'id',
            'variation.availability.id',
            'variation.model',
            'variation.isMain',
			'variation.weightG',
			'variation.releasedAt',			
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