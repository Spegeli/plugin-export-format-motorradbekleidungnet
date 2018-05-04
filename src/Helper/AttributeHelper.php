<?php

namespace ElasticExportMotorradbekleidungNET\Helper;

use Plenty\Modules\Item\Attribute\Contracts\AttributeRepositoryContract;
use Plenty\Modules\Item\Attribute\Contracts\AttributeValueNameRepositoryContract;
use Plenty\Modules\Item\Attribute\Contracts\AttributeValueRepositoryContract;
use Plenty\Modules\Item\Attribute\Models\Attribute;
use Plenty\Modules\Item\Attribute\Models\AttributeValueName;
use Plenty\Plugin\Log\Loggable;

class AttributeHelper
{
    use Loggable;

    /**
     * @var array
     */
    private $itemAttributesCache = [];

    /**
     * @var AttributeValueNameRepositoryContract
     */
    private $attributeValueNameRepositoryContract;

    /**
     * AttributeHelper constructor.
     *
     * @param AttributeValueNameRepositoryContract $attributeValueNameRepositoryContract
     */
    public function __construct(
        AttributeValueNameRepositoryContract $attributeValueNameRepositoryContract)
    {
        $this->attributeValueNameRepositoryContract = $attributeValueNameRepositoryContract;
    }

    /**
     * Get attribute.
     *
     * @param  array $variation
     * @param  string $attribute
     * @return string|bool
     */
    public function getAttributeValue($variation, string $attribute)
    {
        $itemAttributeList = $this->getItemAttributeList($variation);

        if(array_key_exists($attribute, $itemAttributeList))
        {
            return $itemAttributeList[$attribute];
        }

        return '';
    }

    /**
     * Get item attributes for a given variation.
     *
     * @param  array $variation
     * @return array
     */
    private function getItemAttributeList($variation):array
    {
		
		$this->getLogger(__METHOD__)->notice('ElasticExportMotorradbekleidungNET::log.test1', [
		'ItemId'        => $variation['data']['item']['id'],
		'VariationId'   => $variation['id']
		]);
					
        if(!array_key_exists($variation['id'], $this->itemAttributesCache))
        {
			
		$this->getLogger(__METHOD__)->notice('ElasticExportMotorradbekleidungNET::log.test2', [
		'ItemId'        => $variation['data']['item']['id'],
		'VariationId'   => $variation['id']
		]);
		
            $list = array();

            foreach($variation['data']['attributes'] as $attribute)
            {
                if(!is_null($attribute['attributes']['id']))
                {
					//{"propertyId":"5288","lang":"de","name":"Modellname - model_name","description":""}
                    $attributeInfo = $this->attributeValueNameRepositoryContract->findOne($attribute['attributes']['id'], 'de');				

                    // Skip properties which do not have the External Component set up
                    if(!($attributeInfo instanceof AttributeValueName) ||
                        is_null($attributeInfo))
                    {
                        continue;
                    }

					//$list[''.$propertyName['propertyId'].''] = $propertyName['name'];
					
					
					$this->getLogger(__METHOD__)->notice('ElasticExportMotorradbekleidungNET::log.test3', [
					'ItemId'        => $variation['data']['item']['id'],
					'VariationId'   => $variation['id'],
					'AttributeList'  => $attributeInfo
					]);
			
                }
            }

            $this->itemAttributesCache[$variation['id']] = $list;

            $this->getLogger(__METHOD__)->debug('ElasticExportMotorradbekleidungNET::log.variationAttributeList', [
                'ItemId'        => $variation['data']['item']['id'],
                'VariationId'   => $variation['id'],
                'AttributeList'  => count($list) > 0 ? $list : 'no properties'
            ]);
        }

        return $this->itemAttributesCache[$variation['id']];
    }
}