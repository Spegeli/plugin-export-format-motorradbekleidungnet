<?php

namespace ElasticExportMotorradbekleidungNET\Helper;

use Plenty\Modules\Item\Property\Contracts\PropertyNameRepositoryContract;
use Plenty\Modules\Item\Property\Models\PropertyName;
use Plenty\Plugin\Log\Loggable;

class PropertyHelper
{
    use Loggable;

    const PROPERTY_TYPE_TEXT = 'text';
    const PROPERTY_TYPE_SELECTION = 'selection';
    const PROPERTY_TYPE_EMPTY = 'empty';
    const PROPERTY_TYPE_INT = 'int';
    const PROPERTY_TYPE_FLOAT = 'float';

    /**
     * @var array
     */
    private $itemFreeTextCache = [];

    /**
     * @var array
     */
    private $itemPropertyCache = [];

    /**
     * @var PropertyNameRepositoryContract
     */
    private $propertyNameRepository;

    /**
     * PropertyHelper constructor.
     *
     * @param PropertyNameRepositoryContract $propertyNameRepository
     */
    public function __construct(
        PropertyNameRepositoryContract $propertyNameRepository)
    {
        $this->propertyNameRepository = $propertyNameRepository;
    }

    /**
     * Get free text.
     *
     * @param  array $variation
     * @return string
     */
    public function getFreeText($variation):string
    {
        if(!array_key_exists($variation['data']['item']['id'], $this->itemFreeTextCache))
        {
            $freeText = array();

            foreach($variation['data']['properties'] as $property)
            {
                if(!is_null($property['property']['id']) &&
                    $property['property']['valueType'] != 'file' &&
                    $property['property']['valueType'] != 'empty')
                {
                    $propertyName = $this->propertyNameRepository->findOne($property['property']['id'], 'de');

                    // Skip properties which do not have the Component Id set
                    if(!($propertyName instanceof PropertyName) ||
                        is_null($propertyName))
                    {
                        continue;
                    }

                    if($property['property']['valueType'] == self::PROPERTY_TYPE_TEXT)
                    {
                        if(is_array($property['texts']))
                        {
                            $freeText[] = $property['texts'][0]['value'];
                        }
                    }

                    if($property['property']['valueType'] == self::PROPERTY_TYPE_SELECTION)
                    {
                        if(is_array($property['selection']))
                        {
                            $freeText[] = $property['selection'][0]['name'];
                        }
                    }
                }
            }

            $this->itemFreeTextCache[$variation['data']['item']['id']] = implode(' ', $freeText);
        }

        return $this->itemFreeTextCache[$variation['data']['item']['id']];
    }

    /**
     * Get property.
     *
     * @param  array $variation
     * @param  string $property
     * @return string|bool
     */
    public function getProperty($variation, string $property)
    {
        $itemPropertyList = $this->getItemPropertyList($variation);

        if(array_key_exists($property, $itemPropertyList))
        {
            return $itemPropertyList[$property];
        }

        return '';
    }

    /**
     * Get item properties for a given variation.
     *
     * @param  array $variation
     * @return array
     */
    private function getItemPropertyList($variation):array
    {
        if(!array_key_exists($variation['data']['item']['id'], $this->itemPropertyCache))
        {
            $list = array();

            foreach($variation['data']['properties'] as $property)
            {
                if(!is_null($property['property']['id']) &&
                    $property['property']['valueType'] != 'file')
                {
                    $propertyName = $this->propertyNameRepository->findOne($property['property']['id'], 'de');

                    // Skip properties which do not have the External Component set up
                    if(!($propertyName instanceof PropertyName) ||
                        is_null($propertyName))
                    {
                        continue;
                    }

                    if($property['property']['valueType'] == self::PROPERTY_TYPE_TEXT)
                    {
                        if(is_array($property['texts']))
                        {
                            //$list[] = $property['texts'][0]['value'];
							$list[] = 'test 1';
                        }
                    }

                    if($property['property']['valueType'] == self::PROPERTY_TYPE_SELECTION)
                    {
                        if(is_array($property['selection']))
                        {
                            //$list[] = $property['selection'][0]['name'];
							$list[] = 'test 2';
                        }
                    }

					/*
                    if($property['property']['valueType'] == self::PROPERTY_TYPE_EMPTY)
                    {
                        $list[] = $propertyMarketReference->externalComponent;
                    }
					*/

                    if($property['property']['valueType'] == self::PROPERTY_TYPE_INT)
                    {
                        if(!is_null($property['valueInt']))
                        {
                            //$list[] = $property['valueInt'];
							$list[] = 'test 3';
                        }
                    }

                    if($property['property']['valueType'] == self::PROPERTY_TYPE_FLOAT)
                    {
                        if(!is_null($property['valueFloat']))
                        {
                            //$list[] = $property['valueFloat'];
							$list[] = 'test 4';
                        }
                    }

                }
            }

            $this->itemPropertyCache[$variation['data']['item']['id']] = $list;

            $this->getLogger(__METHOD__)->debug('ElasticExportMotorradbekleidungNET::log.variationPropertyList', [
                'ItemId'        => $variation['data']['item']['id'],
                'VariationId'   => $variation['id'],
                'PropertyList'  => count($list) > 0 ? $list : 'no properties'
            ]);
        }

        return $this->itemPropertyCache[$variation['data']['item']['id']];
    }
}