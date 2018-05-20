<?php

namespace ElasticExportMotorradbekleidungNET\Generator;

use ElasticExport\Helper\ElasticExportCoreHelper;
use ElasticExport\Helper\ElasticExportItemHelper;
use ElasticExport\Helper\ElasticExportPriceHelper;
use ElasticExport\Helper\ElasticExportStockHelper;
use ElasticExport\Helper\ElasticExportPropertyHelper;
use ElasticExport\Services\FiltrationService;
use ElasticExportMotorradbekleidungNET\Helper\PropertyHelper;
use ElasticExportMotorradbekleidungNET\Helper\AttributeHelper;
use Plenty\Modules\DataExchange\Contracts\CSVPluginGenerator;
use Plenty\Modules\Helper\Services\ArrayHelper;
use Plenty\Modules\DataExchange\Models\FormatSetting;
use Plenty\Modules\Helper\Models\KeyValue;
use Plenty\Modules\Item\Search\Contracts\VariationElasticSearchScrollRepositoryContract;
use Plenty\Modules\Item\VariationSku\Models\VariationSku;
use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\Log\Loggable;

/**
 * Class MotorradbekleidungNET
 * @package ElasticExportMotorradbekleidungNET\Generator
 */
class MotorradbekleidungNET extends CSVPluginGenerator
{
    use Loggable;

    const DELIMITER = "\t"; // TAB
	
    /**
     * @var ElasticExportCoreHelper
     */
    private $elasticExportHelper;

    /**
     * @var ElasticExportStockHelper
     */
    private $elasticExportStockHelper;

    /**
     * @var ElasticExportPriceHelper
     */
    private $elasticExportPriceHelper;

	/**
	 * @var ElasticExportItemHelper $elasticExportItemHelper
	 */
	private $elasticExportItemHelper;
	
    /**
     * @var ElasticExportPropertyHelper
     */
    private $elasticExportPropertyHelper;	
	
    /**
     * @var ArrayHelper
     */
    private $arrayHelper;

    /**
     * @var PropertyHelper
     */
    private $propertyHelper;

    /**
     * @var AttributeHelper
     */
    private $attributeHelper;
	
    /**
     * @var array
     */
    private $shippingCostCache;

    /**
     * @var array
     */
    private $manufacturerCache;	
	
    /**
     * @var array
     */
    private $imageCache;

    /**
     * @var array
     */
    private $availabilityCache;		
	
    /**
     * @var FiltrationService
     */
    private $filtrationService;
	
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
		PropertyHelper $propertyHelper,
		AttributeHelper $attributeHelper,
		ConfigRepository $configRepository
    )
    {
        $this->arrayHelper = $arrayHelper;
		$this->propertyHelper = $propertyHelper;
		$this->attributeHelper = $attributeHelper;
		$this->configRepository = $configRepository;
    }

    /**
     * Generates and populates the data into the CSV file.
     *
     * @param VariationElasticSearchScrollRepositoryContract $elasticSearch
     * @param array $formatSettings
     * @param array $filter
     */
    protected function generatePluginContent($elasticSearch, array $formatSettings = [], array $filter = [])
    {
        $this->elasticExportHelper = pluginApp(ElasticExportCoreHelper::class);
		$this->elasticExportItemHelper = pluginApp(ElasticExportItemHelper::class);
        $this->elasticExportStockHelper = pluginApp(ElasticExportStockHelper::class);
        $this->elasticExportPriceHelper = pluginApp(ElasticExportPriceHelper::class);
        $this->elasticExportPropertyHelper = pluginApp(ElasticExportPropertyHelper::class);
		
        $settings = $this->arrayHelper->buildMapFromObjectList($formatSettings, 'key', 'value');
        $this->filtrationService = pluginApp(FiltrationService::class, ['settings' => $settings, 'filterSettings' => $filter]);
        
        $this->elasticExportStockHelper->setAdditionalStockInformation($settings);
		
        // Delimiter accepted are TAB or PIPE
        $this->setDelimiter(self::DELIMITER);

        // Add the header of the CSV file
        $this->addCSVContent($this->head());
		
		$this->createAvailabilityCache();

        if($elasticSearch instanceof VariationElasticSearchScrollRepositoryContract)
        {
            // Set the documents per shard for a faster processing
            $elasticSearch->setNumberOfDocumentsPerShard(250);

            // Initiate the counter for the variations limit
            $limitReached = false;
            $limit = 0;
			$shardIterator = 0;

            do 
            {
                // Stop writing if limit is reached
                if($limitReached === true)
                {
                    break;
                }

                // Get the data from Elastic Search
                $resultList = $elasticSearch->execute();

				$shardIterator++;

				// Log the amount of the elasticsearch result once
				if($shardIterator == 1)
				{
					$this->getLogger(__METHOD__)->addReference('total', (int)$resultList['total'])->info('ElasticExportMotorradbekleidungNET::log.esResultAmount');
				}

                if(count($resultList['error']) > 0)
                {
                    $this->getLogger(__METHOD__)->addReference('failedShard', $shardIterator)->error('ElasticExportMotorradbekleidungNET::log.occurredElasticSearchErrors', [
                        'message' => $resultList['error'],
                    ]);
                }

                if(is_array($resultList['documents']) && count($resultList['documents']) > 0)
                {
                    $previousItemId = null;

                    foreach ($resultList['documents'] as $variation)
                    {
                        // Stop and set the flag if limit is reached
                        if($limit == $filter['limit'])
                        {
                            $limitReached = true;
                            break;
                        }

                        // If filtered by stock is set and stock is negative, then skip the variation
                        if($this->filtrationService->filter($variation))
                        {
                            continue;
                        }
						
                        // Skip non-main variations that do not have attributes
                        $attributes = $this->getAttributeNameValueCombination($variation, $settings);
                        if(strlen($attributes) <= 0 && $variation['variation']['isMain'] === false)
                        {
                            continue;
                        }
						
						//Skip variations without barcode
						$barcode_only = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.barcode_only') == "true";
						$barcode = $this->elasticExportHelper->getBarcodeByType($variation, $settings->get('barcode'));
                        if($barcode_only && empty($barcode))
                        {
                            continue;
                        }						
						
                        $attributesvaluecombi = $this->getAttributeValueCombination($variation, $settings);
												
						$drivingstylevalue = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.drivingstyle_active') == true ? $this->getDrivingStyleValue($variation, $settings) : '';
						$gendervalue = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.gender_active') == true ? $this->getGenderValue($variation, $settings) : '';
                        $colorvalue = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.color_active') == true ? $this->getColorValue($variation, $settings) : '';
                        $sizevalue = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.size_active') == true ? $this->getSizeValue($variation, $settings) : '';
						$materialvalue = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.material_active') == true ? $this->getMaterialValue($variation, $settings) : '';
						
                        try
                        {
                            // Set the caches if we have the first variation or when we have the first variation of an item
                            if($previousItemId === null || $previousItemId != $variation['data']['item']['id'])
                            {
                                $previousItemId = $variation['data']['item']['id'];
                                unset($this->shipmentCache, $this->manufacturerCache);

                                // Build the caches arrays
                                $this->buildCaches($variation, $settings);
                            }

                            // New line printed in the CSV file
                            $this->buildRow($variation, $settings, $attributes, $attributesvaluecombi, $gendervalue, $drivingstylevalue, $colorvalue, $sizevalue, $materialvalue);
                        }
                        catch(\Throwable $throwable)
                        {
                            $this->getLogger(__METHOD__)->error('ElasticExportMotorradbekleidungNET::logs.fillRowError', [
                                'message '       => $throwable->getMessage(),
                                'line'           => $throwable->getLine(),
                                'VariationId'    => $variation['id']
                            ]);
                        }

                        // Count the new printed line
                        $limit++;
                    }
                }
                
            } while ($elasticSearch->hasNext());
        }
    }

    /**
     * Creates the header of the CSV file.
     *
     * @return array
     */
    private function head():array
    {
        return array(
            'sku',
            'master_sku',
            'gtin',
			'oem_product_number',
			'name',
			'master_name',
			'variant_name',           
            'manufacturer',
            'description',
			//long_description,
            'image_url',
            'category',
			'size',
			'colour',
			'material',
			'gender',
			'driving_style',
            'price',
            'shipping',			
			'srp',
			'date_changed',
			'date_valid_from',
			'date_valid_to',
			'availability',
			'delivery_period',
			'offered_amount',
			'weight',
			//'currency',      //Aktuell wird nur EUR angeboten
			//'condition',     //Aktuell wird nur Neuware angeboten		
        );
    }	
	
    /**
     * Creates the variation row and prints it into the CSV file.
     *
     * @param array $variation
     * @param KeyValue $settings
     * @param array $attributes
	 * @param array $attributesvaluecombi
	 * @param array $gendervalue
	 * @param array $drivingstylevalue
	 * @param array $colorvalue
	 * @param array $sizevalue
	 * @param array $materialvalue
     */
    private function buildRow($variation, KeyValue $settings, $attributes, $attributesvaluecombi, $gendervalue, $drivingstylevalue, $colorvalue, $sizevalue, $materialvalue)
    {
        // Get and set the price and rrp
        $priceList = $this->getPriceList($variation, $settings);

        // Only variations with the Retail Price greater than zero will be handled
        if(!is_null($priceList['price']) && $priceList['price'] > 0)
        {
			$marketID = (float)$this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.set_marketid');
						
            // Get shipping cost
            $shippingCost = $this->getShippingCost($variation);
            // Get the manufacturer
            $manufacturer = $this->getManufacturer($variation);
            // Get the images only for valid variations
            $imageList = $this->getAdditionalImages($this->getImageList($variation, $settings));

            $data = [
                'sku'                => $this->elasticExportHelper->generateSku($variation['id'], $marketID, 0, (string)$variation['data']['skus'][0]['sku']),
    			'master_sku'         => 'P_' . $variation['data']['item']['id'],
                'gtin'               => $this->elasticExportHelper->getBarcodeByType($variation, $settings->get('barcode')),			
                'oem_product_number' => $variation['data']['variation']['model'],			
		    	'name'               => $this->elasticExportHelper->getMutatedName($variation, $settings) . (strlen($attributes) ? ', ' . $attributes : ''),			
		    	'master_name'        => strlen($attributes) ? $this->elasticExportHelper->getMutatedName($variation, $settings, 256) : '',
		    	'variant_name'       => strlen($attributesvaluecombi) ? $attributesvaluecombi : '',
                'manufacturer'       => $manufacturer,
    			'description'        => $this->elasticExportHelper->getMutatedDescription($variation, $settings),			
                //long_description => ,
                'image_url'          => $imageList,			
	    		'category'           => $this->elasticExportHelper->getCategory((int)$variation['data']['defaultCategories'][0]['id'], $settings->get('lang'), $settings->get('plentyId')),
			    'size'               => strlen($sizevalue) ? $sizevalue : '',
			    'colour'             => strlen($colorvalue) ? $colorvalue : '',
	    		'material'           => strlen($materialvalue) ? $materialvalue : '',
			    'gender'             => !empty($gendervalue) ? $gendervalue : $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.gender_standard'),
			    'driving_style'      => strlen($drivingstylevalue) ? $drivingstylevalue : '',
			    'price'              => $priceList['price'],
			    'shipping'           => $shippingCost,
                'srp'                => $priceList['oldPrice'],		
			    'date_changed'       => $variation['data']['variation']['updatedAt'],
			    'date_valid_from'    => $variation['data']['variation']['releasedAt'],
		    	'date_valid_to'      => $variation['data']['variation']['availableUntil'],
		    	'availability'       => $this->getAvailability($variation),
		    	'delivery_period'    => $this->elasticExportHelper->getAvailability($variation, $settings, false),
                'offered_amount'     => $this->elasticExportStockHelper->getStock($variation),			
		    	'weight'             => number_format($variation['data']['variation']['weightG'] / 1000, 2),
		    	//'currency'         => $priceList['currency'],
		    	//'condition',	     //Aktuell wird nur Neuware angeboten
            ];
			$this->addCSVContent(array_values($data));
		
            $this->getLogger(__METHOD__)->addReference('variationId', ''.$variation['id'].'')->debug('ElasticExportMotorradbekleidungNET::log.debug', [
                'ItemId'         => $variation['data']['item']['id'],
                'VariationId'    => $variation['id'],
                'updatedAt'      => $variation['data']['variation']['updatedAt'],
		    	'releasedAt'     => $variation['data']['variation']['releasedAt'],
		    	'availableUntil' => $variation['data']['variation']['availableUntil']
            ]);		
        }
        else
        {
            $this->getLogger(__METHOD__)->addReference('variationId', ''.$variation['id'].'')->info('ElasticExportMotorradbekleidungNET::log.variationNotPartOfExportPrice', [
                'VariationId' => (string)$variation['id']
            ]);
        }
    }

    /**
     * Get attribute and name value combination for a variation.
     *
     * @param $variation
     * @param KeyValue $settings
     * @return string
     */
    private function getAttributeNameValueCombination($variation, KeyValue $settings):string
    {
        $attributes = '';

        $attributeName = $this->elasticExportHelper->getAttributeName($variation, $settings, ',');
        $attributeValue = $this->elasticExportHelper->getAttributeValueSetShortFrontendName($variation, $settings, ',');

        if(strlen($attributeName) && strlen($attributeValue))
        {
            $attributes = $this->elasticExportHelper->getAttributeNameAndValueCombination($attributeName, $attributeValue);
        }

        return $attributes;
    }

	/**
     * Get attribute and value combination for a variation.
     *
     * @param $variation
     * @param KeyValue $settings
     * @return string
     */
    private function getAttributeValueCombination($variation, KeyValue $settings):string
    {
        $attributesCombi = '';

        $attributeCombiValue = $this->elasticExportHelper->getAttributeValueSetShortFrontendName($variation, $settings, ',');

        if(strlen($attributeCombiValue))
        {
            $attributesCombi = $attributeCombiValue;
        }

        return $attributesCombi;
    }

	/**
     * Get attribute gender value for a variation.
     *
     * @param $variation
     * @param KeyValue $settings
     * @return string
     */
    private function getGenderValue($variation, KeyValue $settings):string
    {
		$config_gender_aom = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.gender_aom');
        $config_gender_ids = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.gender_ids');
        $gender_result = '';		
		if (strlen($config_gender_ids)) {
			$genderIds_array = explode('|', $config_gender_ids);
			foreach ($genderIds_array as $genderId) {		
				$genderValue = $config_gender_aom == "0" ? $this->attributeHelper->getAttributeValue($variation, $genderId) : $this->propertyHelper->getPropertyValue($variation, $genderId);
				if(strlen($genderValue)) {
				    $gender_result = $genderValue;
					break;
			    }
			}
		}	
		return $gender_result;			
    }	

	/**
     * Get attribute driving_style value for a variation.
     *
     * @param $variation
     * @param KeyValue $settings
     * @return string
     */
    private function getDrivingStyleValue($variation, KeyValue $settings):string
    {
		$config_drivingstyle_aom = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.drivingstyle_aom');	
		$config_drivingstyle_ids = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.drivingstyle_ids');	
        $drivingstyle_result = '';		
		if (strlen($config_drivingstyle_ids)) {
			$drivingstyleIds_array = explode('|', $config_drivingstyle_ids);
			foreach ($drivingstyleIds_array as $drivingstyleId) {		
				$drivingstyleValue = $config_drivingstyle_aom == "0" ? $this->attributeHelper->getAttributeValue($variation, $drivingstyleId) : $this->propertyHelper->getPropertyValue($variation, $drivingstyleId);
				if(strlen($drivingstyleValue)) {
				    $drivingstyle_result = $drivingstyleValue;
					break;
			    }
			}
		}	
		return $drivingstyle_result;					
    }
	
	/**
     * Get attribute color value for a variation.
     *
     * @param $variation
     * @param KeyValue $settings
     * @return string
     */
    private function getColorValue($variation, KeyValue $settings):string
    {		
		$config_color_aom = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.color_aom');
        $config_color_ids = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.color_ids');
        $color_result = '';		
		if (strlen($config_color_ids)) {
			$colorIds_array = explode('|', $config_color_ids);
			foreach ($colorIds_array as $colorId) {		
				$colorValue = $config_color_aom == "0" ? $this->attributeHelper->getAttributeValue($variation, $colorId) : $this->propertyHelper->getPropertyValue($variation, $colorId);
				if(strlen($colorValue)) {
				    $color_result = $colorValue;
					break;
			    }
			}
		}	
		return $color_result;		
    }

	/**
     * Get attribute size value for a variation.
     *
     * @param $variation
     * @param KeyValue $settings
     * @return string
     */
    private function getSizeValue($variation, KeyValue $settings):string
    {
		$config_size_aom = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.size_aom');	
		$config_size_ids = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.size_ids');	
        $size_result = '';		
		if (strlen($config_size_ids)) {
			$sizeIds_array = explode('|', $config_size_ids);
			foreach ($sizeIds_array as $sizeId) {		
				$sizeValue = $config_size_aom == "0" ? $this->attributeHelper->getAttributeValue($variation, $sizeId) : $this->propertyHelper->getPropertyValue($variation, $sizeId);
				if(strlen($sizeValue)) {
				    $size_result = $sizeValue;
					break;
			    }
			}
		}	
		return $size_result;			
    }
	
	/**
     * Get material value for a variation.
     *
     * @param $variation
     * @param KeyValue $settings
     * @return string
     */
    private function getMaterialValue($variation, KeyValue $settings):string
    {
		$config_material_aom = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.material_aom');
		$config_material_ids = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.material_ids');	
        $material_result = '';		
		if (strlen($config_material_ids)) {
			$materialIds_array = explode('|', $config_material_ids);
			foreach ($materialIds_array as $materialId) {		
				$materialValue = $config_material_aom == "0" ? $this->attributeHelper->getAttributeValue($variation, $materialId) : $this->propertyHelper->getPropertyValue($variation, $materialId);
				if(strlen($materialValue)) {
				    $material_result = $materialValue;
					break;
			    }
			}
		}	
		return $material_result;						
    }	
	
    /**
     * Get the price list.
     *
     * @param  array    $variation
     * @param  KeyValue $settings
     * @return array
     */
    private function getPriceList(array $variation, KeyValue $settings):array
    {
        $price = $oldPrice = '';

        $priceList = $this->elasticExportPriceHelper->getPriceList($variation, $settings);

        //determinate which price to use as 'price'
        //only use specialPrice if it is set and the lowest price available
        if(    $priceList['specialPrice'] > 0.00
            && $priceList['specialPrice'] < $priceList['price'])
        {
            $price = $priceList['specialPrice'];
        }
        elseif($priceList['price'] > 0.00)
        {
            $price = $priceList['price'];
        }

        //determinate which price to use as 'old_price'
        //only use oldPrice if it is higher than the normal price
        if(    $priceList['recommendedRetailPrice'] > 0.00
            && $priceList['recommendedRetailPrice'] > $price
            && $priceList['recommendedRetailPrice'] > $priceList['price'])
        {
            $oldPrice = $priceList['recommendedRetailPrice'];
        }
        elseif($priceList['price'] > 0.00
            && $priceList['price'] < $price)
        {
            $oldPrice = $priceList['price'];
        }

        return [
            'price'     => $price,
            'oldPrice'  => $oldPrice,
        ];
    }

    /**
     * Get the image list
     *
     * @param  array    $variation
     * @param  KeyValue $settings
     * @return array
     */
    private function getImageList(array $variation, KeyValue $settings):array
    {
        if(!isset($this->imageCache[$variation['data']['item']['id']]))
        {
            $this->imageCache = [];
            $this->imageCache[$variation['data']['item']['id']] = $this->elasticExportHelper->getImageListInOrder($variation, $settings);
        }

        return $this->imageCache[$variation['data']['item']['id']];
    }

    /**
     * Returns a string of all additional picture-URLs separated by ","
     *
     * @param array $imageList
     * @return string
     */
    private function getAdditionalImages(array $imageList):string
    {
        $imageListString = '';

        if(count($imageList))
        {
            $imageListString = implode(' ', $imageList);
        }

        return $imageListString;
    }

    /**
     * Get the shipping cost.
     *
     * @param $variation
     * @return string
     */
    private function getShippingCost($variation):string
    {
        if(isset($this->shippingCostCache) && array_key_exists($variation['data']['item']['id'], $this->shippingCostCache))
        {
            return $this->shippingCostCache[$variation['data']['item']['id']];
        }

        return '';
    }
	
    /**
     * Get the manufacturer name.
     *
     * @param $variation
     * @return string
     */
    private function getManufacturer($variation):string
    {
        if(isset($this->manufacturerCache) && array_key_exists($variation['data']['item']['manufacturer']['id'], $this->manufacturerCache))
        {
            return $this->manufacturerCache[$variation['data']['item']['manufacturer']['id']];
        }
        return '';
    }	
	
    /**
     * Get the availability.
     *
     * @param $variation
     * @return string
     */
    private function getAvailability($variation):string
    {
		if(!isset($this->availabilityCache) || (isset($this->availabilityCache) && !array_key_exists($variation['data']['variation']['availability']['id'], $this->availabilityCache)))
		{
			$this->createAvailabilityCache();
		}
        if(isset($this->availabilityCache) && array_key_exists($variation['data']['variation']['availability']['id'], $this->availabilityCache))
        {
            return $this->availabilityCache[$variation['data']['variation']['availability']['id']];
        }
        return '';		
    }		

    /**
     * Build the cache arrays for the custome availability.
     *
     * @param $variation
     */
    private function createAvailabilityCache()
    {	
		for ($i = 1; $i <= 10; $i++) {
			$this->availabilityCache[$i] = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.availability.'.$i) != "0" ? $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.availability.'.$i) : '';
		}
	}
	
    /**
     * Build the cache arrays for the item variation.
     *
     * @param $variation
     * @param $settings
     */
    private function buildCaches($variation, $settings)
    {
        if(!is_null($variation) && !is_null($variation['data']['item']['id']))
        {
            $shippingCost = $this->elasticExportHelper->getShippingCost($variation['data']['item']['id'], $settings, 0);
			$this->shippingCostCache[$variation['data']['item']['id']] = number_format((float)$shippingCost, 2, '.', '');
				
            if(!is_null($variation['data']['item']['manufacturer']['id']))
            {
                if(!isset($this->manufacturerCache) || (isset($this->manufacturerCache) && !array_key_exists($variation['data']['item']['manufacturer']['id'], $this->manufacturerCache)))
                {
                    $manufacturer = $this->elasticExportHelper->getExternalManufacturerName((int)$variation['data']['item']['manufacturer']['id']);
                    $this->manufacturerCache[$variation['data']['item']['manufacturer']['id']] = $manufacturer;
                }
            }
        }
    }
}