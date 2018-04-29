<?php

namespace ElasticExportMotorradbekleidungNET\Generator;

use ElasticExport\Helper\ElasticExportCoreHelper;
use ElasticExport\Helper\ElasticExportPriceHelper;
use ElasticExport\Helper\ElasticExportStockHelper;
use ElasticExport\Helper\ElasticExportPropertyHelper;
use ElasticExport\Services\FiltrationService;
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
     * @var ArrayHelper
     */
    private $arrayHelper;
	
    /**
     * @var ElasticExportPropertyHelper
     */
    private $elasticExportPropertyHelper;

    /**
     * @var array
     */
    private $shippingCostCache;

    /**
     * @var array
     */
    private $imageCache;

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
		ConfigRepository $configRepository
    )
    {
        $this->arrayHelper = $arrayHelper;
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
        $this->elasticExportStockHelper = pluginApp(ElasticExportStockHelper::class);
        $this->elasticExportPriceHelper = pluginApp(ElasticExportPriceHelper::class);
        $this->elasticExportPropertyHelper = pluginApp(ElasticExportPropertyHelper::class);
        
        $settings = $this->arrayHelper->buildMapFromObjectList($formatSettings, 'key', 'value');
        $this->filtrationService = pluginApp(FiltrationService::class, [$settings, $filter]);
        
        $this->elasticExportStockHelper->setAdditionalStockInformation($settings);
		
        // Delimiter accepted are TAB or PIPE
        $this->setDelimiter(self::DELIMITER);

        // Add the header of the CSV file
        $this->addCSVContent($this->head());

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

                        $attributesvaluecombi = $this->getAttributeValueCombination($variation, $settings);
                        $colorvalue = $this->getColorValue($variation, $settings);
                        $sizevalue = $this->getSizeValue($variation, $settings);
						$materialvalue = $this->getMaterialValue($variation, $settings);
						
                        try
                        {
                            // Set the caches if we have the first variation or when we have the first variation of an item
                            if($previousItemId === null || $previousItemId != $variation['data']['item']['id'])
                            {
                                $previousItemId = $variation['data']['item']['id'];
                                unset($this->shippingCostCache);

                                // Build the caches arrays
                                $this->buildCaches($variation, $settings);
                            }

                            // New line printed in the CSV file
                            $this->buildRow($variation, $settings, $attributes, $attributesvaluecombi, $colorvalue, $sizevalue, $materialvalue);
                        }
                        catch(\Throwable $throwable)
                        {
                            $this->getLogger(__METHOD__)->error('ElasticExportMotorradbekleidungNET::logs.fillRowError', [
                                'message ' => $throwable->getMessage(),
                                'line'     => $throwable->getLine(),
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
            // mandatory
            'sku',
            'master_sku',
            'gtin',
            'name',
            'manufacturer',
            'description',
            'image_url',
            'category',
			'gender',
            'price',
            'shipping',
			'availability',
			'delivery_period',
			'offered_amount',			

            // optional
            'oem_product_number',
			'master_name',
			'variant_name',
			//long_description, //Nicht benötigt da	"description" schon die lange beschreibung ist	
			//'driving_style',  //Merkmal mit Style anlegen
			'srp',
			'weight',
			//'currency',      //Aktuell wird nur EUR angeboten
			//'condition',     //Aktuell wird nur Neuware angeboten
			
			//partially
			'size',
			'colour',
			'material',			
        );
    }	
	
    /**
     * Creates the variation row and prints it into the CSV file.
     *
     * @param array $variation
     * @param KeyValue $settings
     * @param array $attributes
	 * @param array $attributesvaluecombi
	 * @param array $colorvalue
	 * @param array $sizevalue
	 * @param array $materialvalue
     */
    private function buildRow($variation, KeyValue $settings, $attributes, $attributesvaluecombi, $colorvalue, $sizevalue, $materialvalue)
    {
        // Get and set the price and rrp
        $priceList = $this->getPriceList($variation, $settings);
		
        // Get the images only for valid variations
        $imageList = $this->getAdditionalImages($this->getImageList($variation, $settings));
		$marketID = (float)$this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.set_marketid');
		
        $data = [
            // mandatory
            'sku'             => $this->elasticExportHelper->generateSku($variation['id'], $marketID, 0, (string)$variation['data']['skus'][0]['sku']),
			'master_sku'      => 'P_' . $variation['data']['item']['id'],
            'gtin'            => $this->elasticExportHelper->getBarcodeByType($variation, $settings->get('barcode')),			
			'name'            => $this->elasticExportHelper->getMutatedName($variation, $settings) . (strlen($attributes) ? ', ' . $attributes : ''),			
            'manufacturer'    => $this->elasticExportHelper->getExternalManufacturerName((int)$variation['data']['item']['manufacturer']['id']),
            'description'     => $this->elasticExportHelper->getMutatedDescription($variation, $settings),			
            'image_url'       => $imageList,			
			'category'        => $this->elasticExportHelper->getCategory((int)$variation['data']['defaultCategories'][0]['id'], $settings->get('lang'), $settings->get('plentyId')),
			'gender'          => $this->elasticExportPropertyHelper->getProperty($variation, 'gender', $marketID, $settings->get('lang')), //Muss noch angelegt werden
			'price'           => $priceList['price'],
			'shipping'        => $this->getShippingCost($variation),
			'availability'    => $this->elasticExportHelper->getAvailability($variation, $settings, false), //Evl. andere Bezeichung
			'delivery_period' => $this->elasticExportHelper->getAvailability($variation, $settings, false),
            'offered_amount'  => $this->elasticExportStockHelper->getStock($variation),			

			
            // optional
            'oem_product_number' => $variation['data']['variation']['model'],			
			'master_name'        => strlen($attributes) ? $this->elasticExportHelper->getMutatedName($variation, $settings, 256) : '',
			'variant_name'       => strlen($attributesvaluecombi) ? $attributesvaluecombi : '',
			//long_description,  //Nicht benötigt da "description" schon die lange beschreibung ist	
			//'driving_style',   //Merkmal mit Style anlegen
            'srp'                => $priceList['oldPrice'],		
			'weight'             => number_format($variation['data']['variation']['weightG'] / 1000, 2),
			//'currency',        //Aktuell wird nur EUR angeboten
			//'condition',	     //Aktuell wird nur Neuware angeboten
	
			
			//partially
			'size'               => strlen($sizevalue) ? $sizevalue : '',
			'colour'             => strlen($colorvalue) ? $colorvalue : '',
			'material'           => strlen($materialvalue) ? $materialvalue : '',
        ];

        $this->addCSVContent(array_values($data));
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
        $attributes = '';

        $attributeValue = $this->elasticExportHelper->getAttributeValueSetShortFrontendName($variation, $settings, ',');

        if(strlen($attributeValue))
        {
            $attributes = $attributeValue;
        }

        return $attributes;
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
		$config_aom = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.color_aom');
        $config_names = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.color_names');		
		if ($config_aom == "0") {
			$attributes = '';
			$attributeName = $this->elasticExportHelper->getAttributeName($variation, $settings);
			$attributeValue = $this->elasticExportHelper->getAttributeValueSetShortFrontendName($variation, $settings, ',');
			if(strlen($attributeName) && preg_match("/\b(".$config_names.")\b/i", $attributeName))
			{
				$attributes = $attributeValue;
			}
			return $attributes;
		} elseif ($config_aom == "1")  {
			$marketID = (float)$this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.set_marketid');
			$propertys = '';
			$array = explode('|', $config_names);
			foreach ($array as $name) {
			    $propertyValue = $this->elasticExportPropertyHelper->getProperty($variation, $name, $marketID, $settings->get('lang'));
			    if(strlen($propertyValue) {
				    $propertys = $propertyValue;
					break;
			    }
			}
			return $propertys;	
		}				
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
		$config_aom = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.size_aom');	
		$config_names = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.size_names');		
		if ($config_aom == "0") {
			$attributes = '';  
			$attributeName = $this->elasticExportHelper->getAttributeName($variation, $settings);
			$attributeValue = $this->elasticExportHelper->getAttributeValueSetShortFrontendName($variation, $settings, ',');		
			if(strlen($attributeName) && preg_match("/\b(".$config_names.")\b/i", $attributeName))
			{
				$attributes = $attributeValue;
			}
			return $attributes;
		} elseif ($config_aom == "1")  {
			$marketID = (float)$this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.set_marketid');
			$propertys = '';
			$array = explode('|', $config_names);
			foreach ($array as $name) {
			    $propertyValue = $this->elasticExportPropertyHelper->getProperty($variation, $name, $marketID, $settings->get('lang'));
			    if(strlen($propertyValue) {
				    $propertys = $propertyValue;
					break;
			    }
			}
			return $propertys;	
		}			
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
		$config_aom = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.material_aom');
		$config_names = $this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.material_names');		
		if ($config_aom == "0") {
            $attributes = '';
		    $attributeName = $this->elasticExportHelper->getAttributeName($variation, $settings);
            $attributeValue = $this->elasticExportHelper->getAttributeValueSetShortFrontendName($variation, $settings, ',');
            if(strlen($attributeName) && preg_match("/\b(".$config_names.")\b/i", $attributeName))
            {
                $attributes = $attributeValue;
            }
			return $attributes;		
		} elseif ($config_aom == "1")  {
			$marketID = (float)$this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.set_marketid');
			$propertys = '';
			$array = explode('|', $config_names);
			foreach ($array as $name) {
			    $propertyValue = $this->elasticExportPropertyHelper->getProperty($variation, $name, $marketID, $settings->get('lang'));
			    if(strlen($propertyValue) {
				    $propertys = $propertyValue;
					break;
			    }
			}
			return $propertys;	
		}			
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
     * Get if property is set.
     *
     * @param  array $variation
     * @param  string $property
     * @param  KeyValue $settings
     * @return int
     */
    public function isPropertySet($variation, string $property, $settings):int
    {
		$marketID = (float)$this->configRepository->get('ElasticExportMotorradbekleidungNET.settings.set_marketid');
        $itemPropertyList = $this->elasticExportPropertyHelper->getItemPropertyList($variation, $marketID, $settings->get('lang'));

        if(array_key_exists($property, $itemPropertyList))
        {
            return 1;
        }

        return 0;
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
     * Build the cache arrays for the item variation.
     *
     * @param $variation
     * @param $settings
     */
    private function buildCaches($variation, $settings)
    {
        if(!is_null($variation) && !is_null($variation['data']['item']['id']))
        {
            $shippingCost = $this->elasticExportHelper->getShippingCost($variation['data']['item']['id'], $settings);

            if(!is_null($shippingCost))
            {
                $this->shippingCostCache[$variation['data']['item']['id']] = number_format((float)$shippingCost, 2, '.', '');
            }
            else
            {
                $this->shippingCostCache[$variation['data']['item']['id']] = '';
            }
        }
    }
}

