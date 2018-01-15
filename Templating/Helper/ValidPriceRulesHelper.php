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

namespace CoreShop\Bundle\ProductBundle\Templating\Helper;

use CoreShop\Component\Product\Model\ProductInterface;
use CoreShop\Component\Product\Rule\Fetcher\ValidRulesFetcherInterface;

class ValidPriceRulesHelper implements ValidPriceRulesHelperInterface
{
    /**
     * @var ValidRulesFetcherInterface
     */
    protected $validPriceRulesFetcher;

    /**
     * @param ValidRulesFetcherInterface $validPriceRulesFetcher
     */
    public function __construct(ValidRulesFetcherInterface $validPriceRulesFetcher)
    {
        $this->validPriceRulesFetcher = $validPriceRulesFetcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidRules(ProductInterface $product)
    {
        return $this->validPriceRulesFetcher->getValidRules($product);
    }
}
