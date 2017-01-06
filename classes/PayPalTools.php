<?php
/**
 * 2007-2016 PrestaShop
 * 2007 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2016 PrestaShop SA
 *  @copyright 2017 Thirty Bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace PayPalModule;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayPalTools
{
    protected $name = null;

    /**
     * PayPalTools constructor.
     *
     * @param $module_name
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function __construct($module_name)
    {
        $this->name = $module_name;
    }

    /**
     * @param $position
     *
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function moveTopPayments($position)
    {
        $hookPayment = (int) \Hook::getIdByName('payment');
        $moduleInstance = \Module::getInstanceByName($this->name);
        $moduleInfo = \Hook::getModulesFromHook($hookPayment, $moduleInstance->id);


        if ((isset($moduleInfo['position']) && (int) $moduleInfo['position'] > (int) $position) ||
            (isset($moduleInfo['m.position']) && (int) $moduleInfo['m.position'] > (int) $position)) {
            return $moduleInstance->updatePosition($hookPayment, 0, (int) $position);
        }

        return $moduleInstance->updatePosition($hookPayment, 1, (int) $position);
    }

    /**
     * @param $position
     *
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function moveRightColumn($position)
    {
        $hookRight = (int) \Hook::getIdByName('rightColumn');
        $moduleInstance = \Module::getInstanceByName($this->name);
        $moduleInfo = \Hook::getModulesFromHook($hookRight, $moduleInstance->id);


        if ((isset($moduleInfo['position']) && (int) $moduleInfo['position'] > (int) $position) ||
            (isset($moduleInfo['m.position']) && (int) $moduleInfo['m.position'] > (int) $position)) {
            return $moduleInstance->updatePosition($hookRight, 0, (int) $position);
        }

        return $moduleInstance->updatePosition($hookRight, 1, (int) $position);
    }
}
