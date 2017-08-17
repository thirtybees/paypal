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

use PayPalModule\PayPalRestApi;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class PayPalIncontextajaxModuleFrontController
 */
class paypalincontextajaxModuleFrontController extends \ModuleFrontController
{
    /** @var bool $ssl */
    public $ssl = true;

    /**
     * Initialize content
     *
     * @return void
     */
    public function initContent()
    {
        if (\Tools::isSubmit('updateCart')) {
            $this->updateCart();

            return;
        } elseif (\Tools::isSubmit('get_qty')) {
            $this->checkQuantity();

            return;
        }

        $errors = [];
        if (Validate::isLoadedObject(Context::getContext()->cart)) {
            $rest = new PayPalRestApi();
            $payment = $rest->createPayment(false, false, PayPalRestApi::EXPRESS_CHECKOUT_PROFILE);

            if (isset($payment->id)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'paymentID' => $payment->id,
                    'hasError'  => false,
                    'errors'    => [],
                ]);
                die();
            }
        } else {
            $errors[] = $this->module->l('Cart ID not found');
        }

        die(json_encode([
            'hasError' => true,
            'errors'   => $errors,
        ]));
    }

    /**
     * Update the cart before incontext checkout
     */
    protected function updateCart()
    {
        $idProduct = (int) \Tools::getValue('idProduct');
        $idProductAttribute = (int) \Tools::getValue('idProductAttribute');
        if (!$idProductAttribute) {
            $idProductAttribute = null;
        }

        /** @var \Cart $cart */
        $cart = $this->context->cart;
        if (!$cart->id) {
            $cart->add();
        }
        $this->context->cookie->id_cart = $cart->id;

        // Empty cart
        foreach ($cart->getProducts(true) as $product) {
            /** array $product */
            $cart->deleteProduct($product['id_product'], $product['id_product_attribute']);
        }

        $cart->secure_key = \Tools::encrypt(\Tools::passwdGen(20, 'RANDOM'));

        // Add product to cart
        if ($cart->updateQty(1, $idProduct, $idProductAttribute) && $cart->update()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
            ]);
            die();
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
        ]);
        die();
    }

    protected function checkQuantity()
    {
        // Ajax query
        $quantity = \Tools::getValue('get_qty');

        if (\Configuration::get('PS_CATALOG_MODE') == 1) {
            die('0');
        }

        if ($quantity && $quantity > 0) {
            /* Ajax response */
            $idProduct = (int) \Tools::getValue('id_product');
            $idProductAttribute = (int) \Tools::getValue('id_product_attribute');
            $productQuantity = \Product::getQuantity($idProduct, $idProductAttribute);
            $product = new \Product($idProduct);

            if (!$product->available_for_order) {
                die('0');
            }

            if ($productQuantity > 0) {
                die('1');
            }

            if ($productQuantity <= 0 && $product->isAvailableWhenOutOfStock((int) $product->out_of_stock)) {
                die('1');
            }

        }
        die('0');
    }
}
