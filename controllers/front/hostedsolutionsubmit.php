<?php
/**
 * 2017 Thirty Bees
 * 2007-2016 PrestaShop
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
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPalModule\PayPalOrder;

require_once dirname(__FILE__).'/../../paypal.php';

class PayPalHostedsolutionsubmitModuleFrontController extends \ModuleFrontController
{
    /** @var \Context $context */
    public $context;

    /** @var bool $ssl */
    public $ssl = true;

    /** @var \PayPal $module */
    public $module;

    /**
     * PayPalIntegralEvolutionSubmit constructor.
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function __construct()
    {
        $this->context = \Context::getContext();
        parent::__construct();
    }

    /**
     * Initialize content
     */
    public function initContent()
    {
        $idCart = \Tools::getValue('id_cart');

        if ($idCart) {
            // Redirection
            $values = [
                'id_cart' => (int) $idCart,
                'id_module' => (int) \Module::getInstanceByName('paypal')->id,
                'id_order' => (int) \Order::getOrderByCartId((int) $idCart),
            ];

            if (version_compare(_PS_VERSION_, '1.5', '<')) {
                $customer = new \Customer(\Context::getContext()->cookie->id_customer);
                $values['key'] = $customer->secure_key;
                $url = $this->context->link->getModuleLink('paypal', 'hostedsolutionsubmit', [], \Tools::usingSecureMode());
                \Tools::redirectLink($url.'?'.http_build_query($values, '', '&'));
            } else {
                $values['key'] = \Context::getContext()->customer->secure_key;
                $link = \Context::getContext()->link->getModuleLink('paypal', 'submit', $values, \Tools::usingSecureMode());
                \Tools::redirectLink($link);
            }
        } else {
            \Tools::redirectLink(__PS_BASE_URI__);
        }

        exit(0);
    }

    /**
     * Display PayPal order confirmation page
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function displayContent()
    {
        $idOrder = (int) \Tools::getValue('id_order');
        $order = PayPalOrder::getOrderById($idOrder);
        $price = \Tools::displayPrice($order['total_paid'], $this->context->currency);

        $this->context->smarty->assign([
            'order' => $order,
            'price' => $price,
        ]);

        $this->context->smarty->assign([
            'reference_order' => \Order::getUniqReferenceOf($idOrder),
        ]);

        echo $this->module->display(_PS_MODULE_DIR_.'paypal/paypal.php', 'views/templates/front/order-confirmation.tpl');
    }
}
