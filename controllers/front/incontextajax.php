<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class MollieIdealajaxModuleFrontController
 */
class MollieIdealajaxModuleFrontController extends ModuleFrontController
{
    /** @var bool $ssl */
    public $ssl = true;

    /**
     * Initialize content
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function init()
    {
        if (Tools::isSubmit('createCart')) {
            $this->updateCart();

            exit;
        } elseif (Tools::isSubmit('get_qty')) {
            $this->checkQuantity();

            exit;
        }

        die(json_encode([
            'success'  => false,
            'errors'   => [],
        ]));
    }

    /**
     * Update the cart before incontext checkout
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function createCart()
    {
        $idProduct = (int) Tools::getValue('idProduct');
        $idProductAttribute = (int) Tools::getValue('idProductAttribute');
        $quantity = (int) Tools::getValue('quantity');
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
            /** @var array $product */
            $cart->deleteProduct($product['id_product'], $product['id_product_attribute']);
        }

        $cart->secure_key = Tools::encrypt(Tools::passwdGen(20, 'RANDOM'));

        // Add product to cart
        if ($cart->updateQty(1, $idProduct, $idProductAttribute) && $cart->update()) {
            header('Content-Type: application/json;charset=UTF-8');
            die(json_encode([
                'success' => true,
            ]));
        }

        header('Content-Type: application/json;charset=UTF-8');
        die(json_encode([
            'success' => false,
        ]));
    }

    /**
     * Check quantity in cart
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function checkQuantity()
    {
        // Ajax query
        $quantity = Tools::getValue('get_qty');

        if (Configuration::get('PS_CATALOG_MODE')) {
            die(json_encode([
                'success'  => true,
                'in_stock' => false,
            ]));
        }

        if ($quantity && $quantity > 0) {
            $idProduct = (int) Tools::getValue('id_product');
            $idProductAttribute = (int) Tools::getValue('id_product_attribute');
            $productQuantity = Product::getQuantity($idProduct, $idProductAttribute);
            $product = new Product($idProduct);

            if (!$product->available_for_order) {
                die(json_encode([
                    'success'  => true,
                    'in_stock' => false,
                ]));
            }

            if ($productQuantity > 0) {
                die(json_encode([
                    'success'  => true,
                    'in_stock' => true,
                ]));
            }

            if ($productQuantity <= 0 && $product->isAvailableWhenOutOfStock((int) $product->out_of_stock)) {
                die(json_encode([
                    'success'  => true,
                    'in_stock' => true,
                ]));
            }

        }

        die(json_encode([
            'success'  => true,
            'in_stock' => false,
        ]));
    }
}
