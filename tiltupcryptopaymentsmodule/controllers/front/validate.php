<?php

/**
 * TiltUp_TiltUpCryptoPaymentsModule extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author         TiltUp Sp. z o. o.
 * @copyright      Copyright (c) 2023-2031
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */
class TiltUpCryptoPaymentsModuleValidateModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if (!($this->module instanceof Tiltupcryptopaymentsmodule)) {
            Tools::redirect('index.php?controller=order&step=1');

            return;
        }

        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');

            return;
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'tiltupcryptopaymentsmodule') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            exit($this->trans(
                'This payment method is not available.',
                [],
                'Modules.Tiltupcryptopaymentsmodule.Shop'
            ));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');

            return;
        }

        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $mailVars = [];
        $orderStatusId =
            (int) Configuration::getGlobalValue(TiltUpCryptoPaymentsModule::CRYPTO_PAYMENT_PENDING_STATUS_CONFIG);

        // This actually creates the order
        $this->module->validateOrder(
            (int) $cart->id,
            $orderStatusId,
            $total,
            $this->module->displayName,
            null,
            $mailVars,
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        if ((bool) Configuration::get(TiltUpCryptoPaymentsModule::REDIRECT_IMMEDIATE_CONFIG) === true) {
            $order = new Order($this->module->currentOrder);
            Tools::redirect($this->module->buildTiltUpRedirectUrl($order));
        } else {
            Tools::redirect($this->context->link->getPageLink('order-confirmation', null, null, [
                'id_order' => $this->module->currentOrder,
                'id_cart' => (int) $cart->id,
                'id_module' => (int) $this->module->id,
                'key' => $customer->secure_key,
            ]));
        }
    }
}
