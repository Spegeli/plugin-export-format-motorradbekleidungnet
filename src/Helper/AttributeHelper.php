<?php

namespace ElasticExportMotorradbekleidungNET\Helper;

use ElasticExport\Helper\ElasticExportPropertyHelper;
use ElasticExportMotorradbekleidungNET\Generator\GoogleShopping;
use Plenty\Modules\Helper\Models\KeyValue;
use Plenty\Modules\Item\Attribute\Contracts\AttributeRepositoryContract;
use Plenty\Modules\Item\Attribute\Contracts\AttributeValueNameRepositoryContract;
use Plenty\Modules\Item\Attribute\Contracts\AttributeValueRepositoryContract;
use Plenty\Modules\Item\Attribute\Models\Attribute;
use Plenty\Modules\Item\Attribute\Models\AttributeValueName;
use Plenty\Repositories\Models\PaginatedResult;

class AttributeHelper
{
    /**
     * @var AttributeRepositoryContract $attributeRepositoryContract
     */
    private $attributeRepositoryContract;
    /**
     * @var AttributeValueRepositoryContract
     */
    private $attributeValueRepositoryContract;
    /**
     * @var AttributeValueNameRepositoryContract
     */
    private $attributeValueNameRepositoryContract;
	/**
	 * @var ElasticExportPropertyHelper
	 */
    private $elasticExportPropertyHelper;

    /**
     * AttributeHelper constructor.
     * @param AttributeRepositoryContract $attributeRepositoryContract
     * @param AttributeValueRepositoryContract $attributeValueRepositoryContract
     * @param AttributeValueNameRepositoryContract $attributeValueNameRepositoryContract
     */
    public function __construct(
        AttributeRepositoryContract $attributeRepositoryContract,
        AttributeValueRepositoryContract $attributeValueRepositoryContract,
        AttributeValueNameRepositoryContract $attributeValueNameRepositoryContract)
    {
        $this->attributeRepositoryContract = $attributeRepositoryContract;
        $this->attributeValueRepositoryContract = $attributeValueRepositoryContract;
        $this->attributeValueNameRepositoryContract = $attributeValueNameRepositoryContract;
    }

    /**
     * Get attribute and name value combination for a variation.
     *
     * @param $variation
     * @param KeyValue $settings
     * @return string
     */
    public function getAttributeNameValueCombination($variation, KeyValue $settings):string
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
    public function getAttributeValueCombination($variation, KeyValue $settings):string
    {
        $attributesCombi = '';

        $attributeCombiValue = $this->elasticExportHelper->getAttributeValueSetShortFrontendName($variation, $settings, ',');

        if(strlen($attributeCombiValue))
        {
            $attributesCombi = $attributeCombiValue;
        }

        return $attributesCombi;
    }
}