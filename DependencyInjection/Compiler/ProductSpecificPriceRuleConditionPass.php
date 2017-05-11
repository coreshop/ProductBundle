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

namespace CoreShop\Bundle\ProductBundle\DependencyInjection\Compiler;

use CoreShop\Bundle\RuleBundle\DependencyInjection\Compiler\RegisterActionConditionPass;

final class ProductSpecificPriceRuleConditionPass extends RegisterActionConditionPass
{
    protected function getIdentifier()
    {
        return 'coreshop.product_specific_price_rule.conditions';
    }

    protected function getTagIdentifier()
    {
        return 'coreshop.product_specific_price_rule.condition';
    }

    protected function getRegistryIdentifier()
    {
        return 'coreshop.registry.product_specific_price_rule.conditions';
    }

    protected function getFormRegistryIdentifier()
    {
        return 'coreshop.form_registry.product_specific_price_rule.conditions';
    }
}
