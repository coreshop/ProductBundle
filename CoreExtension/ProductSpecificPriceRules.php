<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2019 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Bundle\ProductBundle\CoreExtension;

use CoreShop\Bundle\ProductBundle\Form\Type\ProductSpecificPriceRuleType;
use CoreShop\Component\Pimcore\BCLayer\CustomResourcePersistingInterface;
use CoreShop\Component\Pimcore\BCLayer\LazyLoadedFields;
use CoreShop\Component\Product\Model\ProductInterface;
use CoreShop\Component\Product\Model\ProductSpecificPriceRuleInterface;
use CoreShop\Component\Product\Repository\ProductSpecificPriceRuleRepositoryInterface;
use JMS\Serializer\SerializationContext;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Webmozart\Assert\Assert;

class ProductSpecificPriceRules extends Data implements CustomResourcePersistingInterface
{
    /**
     * Static type of this element.
     *
     * @var string
     */
    public $fieldtype = 'coreShopProductSpecificPriceRules';

    /**
     * @var int
     */
    public $height;

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private function getContainer()
    {
        return \Pimcore::getContainer();
    }

    /**
     * @return ProductSpecificPriceRuleRepositoryInterface
     */
    private function getProductSpecificPriceRuleRepository()
    {
        return $this->getContainer()->get('coreshop.repository.product_specific_price_rule');
    }

    /**
     * @return \Symfony\Component\Form\FormFactoryInterface
     */
    private function getFormFactory()
    {
        return $this->getContainer()->get('form.factory');
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return \JMS\Serializer\SerializerInterface
     */
    private function getSerializer()
    {
        return $this->getContainer()->get('jms_serializer');
    }

    /**
     * @return array
     */
    private function getConfigActions()
    {
        return $this->getContainer()->getParameter('coreshop.product_specific_price_rule.actions');
    }

    /**
     * @return array
     */
    private function getConfigConditions()
    {
        return $this->getContainer()->getParameter('coreshop.product_specific_price_rule.conditions');
    }

    /**
     * @param mixed $object
     *
     * @return ProductSpecificPriceRuleInterface[]
     */
    public function preGetData($object)
    {
        Assert::isInstanceOf($object, ProductInterface::class);

        //TODO: Remove once CoreShop requires min Pimcore 5.5
        if (method_exists($object, 'getObjectVar')) {
            $data = $object->getObjectVar($this->getName());
        } else {
            $data = $object->{$this->getName()};
        }

        /*if (!method_exists($object, 'getO__loadedLazyFields')) {
            return $data;
        }*/

        if (!LazyLoadedFields::hasLazyKey($object, $this->getName())) {
            $data = $this->load($object, ['force' => true]);

            $setter = 'set' . ucfirst($this->getName());
            if (method_exists($object, $setter)) {
                $object->$setter($data);
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function preSetData($object, $data, $params = [])
    {
        if (!LazyLoadedFields::hasLazyKey($object, $this->getName())) {
            LazyLoadedFields::addLazyKey($object, $this->getName());
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getDiffDataForEditMode($data, $object = null, $params = [])
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        return [];
    }

    /**
     * @param mixed $data
     * @param null  $object
     * @param array $params
     *
     * @return ProductSpecificPriceRuleInterface[]
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $data = [
            'actions' => array_keys($this->getConfigActions()),
            'conditions' => array_keys($this->getConfigConditions()),
            'rules' => [],
        ];

        if ($object instanceof ProductInterface) {
            $prices = $this->load($object, ['force' => true]);

            $context = SerializationContext::create();
            $context->setSerializeNull(true);
            $context->setGroups(['Default', 'Detailed']);

            $serializedData = $this->getSerializer()->serialize($prices, 'json', $context);

            $data['rules'] = json_decode($serializedData, true);
        }

        return $data;
    }

    /**
     * @param mixed $data
     * @param null  $object
     * @param array $params
     *
     * @return ProductSpecificPriceRuleInterface[]
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $prices = [];
        $errors = [];

        if ($data && $object instanceof Concrete) {
            foreach ($data as $dataRow) {
                $ruleId = isset($dataRow['id']) && is_numeric($dataRow['id']) ? $dataRow['id'] : null;

                $storedRule = null;

                if ($ruleId !== null) {
                    $storedRule = $this->getProductSpecificPriceRuleRepository()->find($ruleId);
                }

                $form = $this->getFormFactory()->createNamed('', ProductSpecificPriceRuleType::class, $storedRule);

                $form->submit($dataRow);

                if ($form->isValid()) {
                    $formData = $form->getData();
                    $formData->setProduct($object->getId());

                    $prices[] = $formData;
                } else {
                    foreach ($form->getErrors(true, true) as $e) {
                        $errorMessageTemplate = $e->getMessageTemplate();
                        foreach ($e->getMessageParameters() as $key => $value) {
                            $errorMessageTemplate = str_replace($key, $value, $errorMessageTemplate);
                        }

                        $errors[] = sprintf('%s: %s', $e->getOrigin()->getConfig()->getName(), $errorMessageTemplate);
                    }

                    throw new \Exception(implode(PHP_EOL, $errors));
                }
            }
        }

        return $prices;
    }

    /**
     * @param Concrete $object
     * @param array    $params
     */
    public function save($object, $params = [])
    {
        if ($object instanceof ProductInterface) {
            //TODO: Remove once CoreShop requires min Pimcore 5.5
            if (method_exists($object, 'getObjectVar')) {
                $existingPriceRules = $object->getObjectVar($this->getName());
            } else {
                $existingPriceRules = $object->{$this->getName()};
            }

            $all = $this->load($object, ['force' => true]);
            $founds = [];

            if (is_array($existingPriceRules)) {
                foreach ($existingPriceRules as $price) {
                    if ($price instanceof ProductSpecificPriceRuleInterface) {
                        $price->setProduct($object->getId());

                        $this->getEntityManager()->persist($price);
                        $this->getEntityManager()->flush();

                        $founds[] = $price->getId();
                    }
                }
            }

            foreach ($all as $price) {
                if (!in_array($price->getId(), $founds)) {
                    $this->getEntityManager()->remove($price);
                }
            }

            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load($object, $params = [])
    {
        if (isset($params['force']) && $params['force']) {
            return $this->getProductSpecificPriceRuleRepository()->findForProduct($object);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($object, $params = [])
    {
        if ($object instanceof ProductInterface) {
            $all = $this->load($object, ['force' => true]);

            foreach ($all as $price) {
                $this->getEntityManager()->remove($price);
            }

            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param mixed $data
     * @param null  $relatedObject
     * @param mixed $params
     * @param null  $idMapper
     *
     * @return ProductSpecificPriceRuleInterface[]
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($data, $relatedObject = null, $params = [], $idMapper = null)
    {
        return $this->getDataFromEditmode($this->arrayCastRecursive($data), $relatedObject, $params);
    }

    /**
     * @param \stdClass[] $array
     *
     * @return array
     */
    protected function arrayCastRecursive($array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = $this->arrayCastRecursive($value);
                }
                if ($value instanceof \stdClass) {
                    $array[$key] = $this->arrayCastRecursive((array) $value);
                }
            }
        }
        if ($array instanceof \stdClass) {
            return $this->arrayCastRecursive((array) $array);
        }

        return $array;
    }
}
