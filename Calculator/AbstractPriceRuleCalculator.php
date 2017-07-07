<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
*/

namespace CoreShop\Bundle\ProductBundle\Calculator;

use CoreShop\Component\Product\Calculator\ProductPriceCalculatorInterface;
use CoreShop\Component\Product\Model\ProductInterface;
use CoreShop\Component\Product\Rule\Action\ProductPriceActionProcessorInterface;
use CoreShop\Component\Registry\ServiceRegistryInterface;
use CoreShop\Component\Resource\Repository\RepositoryInterface;
use CoreShop\Component\Rule\Condition\RuleValidationProcessorInterface;
use CoreShop\Component\Rule\Model\ActionInterface;
use CoreShop\Component\Rule\Model\RuleInterface;

abstract class AbstractPriceRuleCalculator implements ProductPriceCalculatorInterface
{
    /**
     * @var RepositoryInterface
     */
    protected $productPriceRuleRepository;

    /**
     * @var RuleValidationProcessorInterface
     */
    protected $ruleValidationProcessor;

    /**
     * @var ServiceRegistryInterface
     */
    protected $actionServiceRegistry;

    /**
     * @param RepositoryInterface              $productPriceRuleRepository
     * @param RuleValidationProcessorInterface $ruleValidationProcessor
     * @param ServiceRegistryInterface         $actionServiceRegistry
     */
    public function __construct(
        RepositoryInterface $productPriceRuleRepository,
        RuleValidationProcessorInterface $ruleValidationProcessor,
        ServiceRegistryInterface $actionServiceRegistry
    ) {
        $this->productPriceRuleRepository = $productPriceRuleRepository;
        $this->ruleValidationProcessor = $ruleValidationProcessor;
        $this->actionServiceRegistry = $actionServiceRegistry;
    }

    /**
     * @param $subject
     *
     * @return mixed
     */
    abstract protected function getPriceRules($subject);

    /**
     * {@inheritdoc}
     */
    public function getPrice(ProductInterface $subject)
    {
        $price = 0;

        /**
         * @var RuleInterface[]
         */
        $rules = $this->getPriceRules($subject);

        if (is_array($rules)) {
            foreach ($rules as $rule) {
                if ($rule->getActive()) {
                    if ($this->ruleValidationProcessor->isValid($subject, $rule)) {
                        /**
                         * @var ActionInterface
                         */
                        foreach ($rule->getActions() as $action) {
                            $processor = $this->actionServiceRegistry->get($action->getType());

                            if ($processor instanceof ProductPriceActionProcessorInterface) {
                                $actionPrice = $processor->getPrice($subject, $action->getConfiguration());

                                if (false !== $actionPrice && null !== $actionPrice) {
                                    $price = $actionPrice;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $price === 0 ? false : $price;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscount(ProductInterface $subject, $price)
    {
        $discount = 0;

        /**
         * @var RuleInterface[]
         */
        $rules = $this->getPriceRules($subject);

        if (is_array($rules)) {
            foreach ($rules as $rule) {
                if ($this->ruleValidationProcessor->isValid($subject, $rule)) {
                    foreach ($rule->getActions() as $action) {
                        $processor = $this->actionServiceRegistry->get($action->getType());

                        if ($processor instanceof ProductPriceActionProcessorInterface) {
                            $discount += $processor->getDiscount($subject, $price, $action->getConfiguration());
                        }
                    }
                }
            }
        }

        return $discount;
    }
}
