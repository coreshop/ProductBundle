<?php

declare(strict_types=1);

/*
 * CoreShop
 *
 * This source file is available under two different licenses:
 *  - GNU General Public License version 3 (GPLv3)
 *  - CoreShop Commercial License (CCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.org)
 * @license    https://www.coreshop.org/license     GPLv3 and CCL
 *
 */

namespace CoreShop\Bundle\ProductBundle\CoreExtension;

use CoreShop\Bundle\ResourceBundle\Pimcore\CacheMarshallerInterface;
use CoreShop\Component\Product\Model\ProductInterface;
use CoreShop\Component\Product\Model\ProductUnitDefinitionInterface;
use CoreShop\Component\Product\Model\ProductUnitDefinitionsInterface;
use CoreShop\Component\Resource\Model\ResourceInterface;
use CoreShop\Component\Resource\Repository\RepositoryInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

/**
 * @psalm-suppress InvalidReturnType, InvalidReturnStatement
 */
class ProductUnitDefinition extends Data implements
    Data\ResourcePersistenceAwareInterface,
    Data\QueryResourcePersistenceAwareInterface,
    Data\CustomVersionMarshalInterface,
    CacheMarshallerInterface
{
    /**
     * Static type of this element.
     *
     * @var string
     */
    public $fieldtype = 'coreShopProductUnitDefinition';

    /**
     * Type for the generated phpdoc.
     *
     * @var string
     */
    public $phpdocType = '\\' . ProductUnitDefinitionInterface::class;

    /**
     * @var bool
     */
    public $allowEmpty = false;

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . ProductUnitDefinitionInterface::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . ProductUnitDefinitionInterface::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . ProductUnitDefinitionInterface::class;
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . ProductUnitDefinitionInterface::class;
    }

    public function getQueryColumnType(): string|array
    {
        return 'int(11)';
    }

    public function getColumnType(): string|array
    {
        return 'int(11)';
    }

    public function isDiffChangeAllowed($object, $params = [])
    {
        return false;
    }

    public function getDiffDataForEditMode($data, $object = null, $params = [])
    {
        return [];
    }

    public function preSetData($object, $data, $params = [])
    {
        if (is_int($data) || is_string($data)) {
            if ((int) $data) {
                return $this->getDataFromResource($data, $object, $params);
            }
        }

        return $data;
    }

    public function preGetData($object, $params = [])
    {
        /**
         * @var Concrete $object
         */
        $data = $object->getObjectVar($this->getName());

        if ($data instanceof ResourceInterface && $data->getId()) {
            //Reload from Database, but only if available
            $tmpData = $this->getRepository()->find($data->getId());

            if ($tmpData instanceof ResourceInterface) {
                //Dirty Fix, Pimcore sometimes calls properties without getter
                //This could cause Problems with translations, therefore, we need to set
                //the value here
                $object->setValue($this->getName(), $tmpData);

                return $tmpData;
            }
        }

        return $data;
    }

    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof ProductUnitDefinitionInterface) {
            return $data->getId();
        }

        return null;
    }

    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ((int) $data > 0) {
            return $this->getRepository()->find($data);
        }

        return null;
    }

    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        if ($data instanceof ProductUnitDefinitionInterface) {
            return $data->getId();
        }

        return null;
    }

    public function createDataCopy(Concrete $object, $data)
    {
        if (!$data instanceof ProductUnitDefinitionsInterface) {
            return null;
        }

        if (!$object instanceof ProductInterface) {
            return null;
        }

        $data->setProduct($object);

        $reflectionClass = new \ReflectionClass($data);
        $property = $reflectionClass->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($data, null);

        foreach ($data->getUnitDefinitions() as $unitDefinition) {
            $reflectionClass = new \ReflectionClass($unitDefinition);
            $property = $reflectionClass->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($unitDefinition, null);
        }

        return $data;
    }

    public function marshalVersion($object, $data)
    {
        return $this->getDataForEditmode($data, $object);
    }

    public function unmarshalVersion($object, $data)
    {
        if (is_array($data) && isset($data['id'])) {
            return $this->getRepository()->find($data['id']);
        }

        return null;
    }

    public function marshalRecycleData($object, $data)
    {
        return $this->marshalVersion($object, $data);
    }

    public function unmarshalRecycleData($object, $data)
    {
        return $this->unmarshalVersion($object, $data);
    }

    public function marshalForCache(Concrete $concrete, mixed $data): mixed
    {
        return $this->marshalVersion($concrete, $data);
    }

    public function unmarshalForCache(Concrete $concrete, mixed $data): mixed
    {
        return $this->unmarshalVersion($concrete, $data);
    }

    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $parsedData = [
            'id' => null,
            'conversationRate' => null,
            'precision' => null,
            'unitName' => null,
        ];

        if ($data instanceof ProductUnitDefinitionInterface) {
            $parsedData = [
                'id' => $data->getId(),
                'conversationRate' => $data->getConversionRate(),
                'precision' => $data->getPrecision(),
                'unitName' => $data->getUnit()->getName(),
                'fullLabel' => $data->getUnit()->getFullLabel(),
                'fullPluralLabel' => $data->getUnit()->getFullPluralLabel(),
                'shortLabel' => $data->getUnit()->getShortLabel(),
                'shortPluralLabel' => $data->getUnit()->getShortPluralLabel(),
            ];
        }

        return $parsedData;
    }

    public function isEmpty($data)
    {
        return !$data instanceof ProductUnitDefinitionInterface;
    }

    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $data;
    }

    public function getForCsvExport($object, $params = [])
    {
        return '';
    }

    /**
     * @return RepositoryInterface
     */
    protected function getRepository()
    {
        return \Pimcore::getContainer()->get('coreshop.repository.product_unit_definition');
    }
}
