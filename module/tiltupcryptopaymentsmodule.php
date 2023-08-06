<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class TiltUpCryptoPaymentsModule extends PaymentModule
{
    const MERCHANT_ID_CONFIG = 'TILTUP_MERCHANT_ID';
    const SHOP_ID_CONFIG = 'TILTUP_SHOP_ID';
    const IS_STAGING_CONFIG = 'IS_STAGING';
    const AWAITING_CRYPTO_ORDER_STATUS_CONFIG = 'AWAITING_CRYPTO_ORDER_STATUS';
    const TILTUP_ECOMMERCE_TYPE = 'PRESTASHOP';

    public function __construct()
    {
        $this->name = 'tiltupcryptopaymentsmodule';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'TiltUp Engineering';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->controllers = ['redirect'];

        parent::__construct();

        $this->displayName = $this->l('TiltUp Crypto Payments');
        $this->description = $this->l('One-click fast and secure crypto payments.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return (
            parent::install() && $this->enableWebServices() && $this->registerHook('paymentOptions') && $this->registerHook('displayPaymentReturn')
            && $this->installOrderState()
        );
    }


    /**
     * This method handles the module's configuration page
     * @return string The page's HTML content
     */
    public function getContent(): string
    {
        $output = '';

        // this part is executed only when the form is submitted
        if (Tools::isSubmit('submit' . $this->name)) {
            // retrieve the value set by the user
            $merchantId = (string)Tools::getValue(self::MERCHANT_ID_CONFIG);
            $shopId = (string)Tools::getValue(self::SHOP_ID_CONFIG);
            $isStaging = (bool)Tools::getValue(self::IS_STAGING_CONFIG);

            // check that the value is valid
            if (empty($merchantId) || !Validate::isGenericName($merchantId)) {
                // invalid value, show an error
                $output = $this->displayError($this->l('Invalid Configuration value'));
            } else {
                // value is ok, update it and display a confirmation message
                Configuration::updateValue(self::MERCHANT_ID_CONFIG, $merchantId);
                Configuration::updateValue(self::SHOP_ID_CONFIG, $shopId);
                Configuration::updateValue(self::IS_STAGING_CONFIG, $isStaging);

                $output = $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        // display any message, then the form
        return $output . $this->displayForm();
    }

    /**
     * Builds the configuration form
     * @return string HTML code
     */
    public function displayForm(): string
    {
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('TiltUp Settings'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('TiltUp Merchant ID'),
                        'name' => self::MERCHANT_ID_CONFIG,
                        'size' => 32,
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('TiltUp Shop ID'),
                        'name' => self::SHOP_ID_CONFIG,
                        'size' => 32,
                        'required' => true,
                    ],
                    [
                        'type' => 'radio',
                        'label' => $this->l('Staging Mode'),
                        'name' => self::IS_STAGING_CONFIG,
                        'is_bool' => true,
                        'class' => 't',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                        'required' => false,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        // Default language
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');

        // Load current value into the form
        $helper->fields_value[self::MERCHANT_ID_CONFIG] = Tools::getValue(self::MERCHANT_ID_CONFIG, Configuration::get(self::MERCHANT_ID_CONFIG));
        $helper->fields_value[self::SHOP_ID_CONFIG] = Tools::getValue(self::SHOP_ID_CONFIG, Configuration::get(self::SHOP_ID_CONFIG));
        $helper->fields_value[self::IS_STAGING_CONFIG] = Tools::getValue(self::IS_STAGING_CONFIG, Configuration::get(self::IS_STAGING_CONFIG));

        return $helper->generateForm([$form]);
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $cart = $this->context->cart;
        $total = $this->context->getCurrentLocale()->formatPrice(
            $cart->getOrderTotal(true, Cart::BOTH),
            (new Currency($cart->id_currency))->iso_code
        );
        $taxLabel = '';
        if ($this->context->country->display_tax_label) {
            $taxLabel = $this->trans(
                '(tax incl.)',
                [],
                'Modules.Tiltupcryptopaymentsmodule.Admin'
            );
        }
        $this->smarty->assign([
            'totalPrice' => $total,
            'taxLabel' => $taxLabel
        ]);
        $cryptoPaymentOption = new PaymentOption();
        $cryptoPaymentOption->setModuleName($this->name)
            ->setCallToActionText($this->trans(
                'Pay with TiltUp',
                [],
                'Modules.Tiltupcryptopaymentsmodule.Admin'
            ))
            ->setAction($this->context->link->getModuleLink($this->name, 'validate', [], true))
            ->setAdditionalInformation($this->fetch('module:tiltupcryptopaymentsmodule/views/templates/hook/paymentOptionInfo.tpl'))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/logo.png'));

        return [$cryptoPaymentOption];
    }

    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }


        $merchantOrderId = $params['order']->reference;
        $tiltUpRedirectUrl = $this->buildTiltUpRedirectUrl($merchantOrderId);

        $totalAmount = $params['order']->getOrdersTotalPaid();
        $this->smarty->assign([
            'totalAmount' => $this->context->getCurrentLocale()->formatPrice(
                $totalAmount,
                (new Currency($params['order']->id_currency))->
                iso_code
            ),
            'tiltUpRedirectUrl' => $tiltUpRedirectUrl,
            'orderId' => $merchantOrderId
        ]);

        return $this->fetch('module:tiltupcryptopaymentsmodule/views/templates/hook/postPaymentInfo.tpl');
    }

    private function checkCurrency($cart): bool
    {
        $currency_order = new Currency((int)($cart->
        id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id ==
                    $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    private function enableWebServices(): bool
    {
        $wsEnabled = Configuration::updateValue('PS_WEBSERVICE', 1);
        $apiAccess = new WebserviceKey();
        $apiAccess->key = bin2hex(random_bytes(16));
        $apiAccess->description = 'TILTUP WEBHOOK ACCESS KEY';
        $apiAccess->save();

        $permissions = [
            'customers' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'PATCH' => 1, 'DELETE' => 1, 'HEAD' => 1],
            'orders' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'PATCH' => 1, 'DELETE' => 1, 'HEAD' => 1],
        ];

        WebserviceKey::setPermissionForAccount($apiAccess->id, $permissions);

        return $wsEnabled;
    }

    private function installOrderState(): bool
    {
        if (Configuration::getGlobalValue(self::AWAITING_CRYPTO_ORDER_STATUS_CONFIG)) {
            $orderState = new OrderState((int)Configuration::getGlobalValue(self::AWAITING_CRYPTO_ORDER_STATUS_CONFIG));
            if (Validate::isLoadedObject($orderState) && $this->name === $orderState->module_name) {
                return true;
            }
        }

        return $this->createAwaitingCryptoOrderState(
            self::AWAITING_CRYPTO_ORDER_STATUS_CONFIG,
            [
                'en' => 'Awaiting crypto payment',
                'fr' => 'En attente de paiement en crypto',
            ],
            '#E5C143'
        );
    }

    private function createAwaitingCryptoOrderState($configurationKey, array $nameByLangIsoCode, $color, $isLogable = false, $isPaid = false, $isInvoice = false, $isShipped = false, $isDelivery = false, $isPdfDelivery = false, $isPdfInvoice = false, $isSendEmail = false, $template = '', $isHidden = false, $isUnremovable = true, $isDeleted = false): bool
    {
        $tabNameByLangId = [];

        foreach ($nameByLangIsoCode as $langIsoCode => $name) {
            foreach (Language::getLanguages(false) as $language) {
                if (Tools::strtolower($language['iso_code']) === $langIsoCode) {
                    $tabNameByLangId[(int)$language['id_lang']] = $name;
                } elseif (isset($nameByLangIsoCode['en'])) {
                    $tabNameByLangId[(int)$language['id_lang']] =
                        $nameByLangIsoCode['en'];
                }
            }
        }

        $orderState = new OrderState();
        $orderState->module_name = $this->name;
        $orderState->name = $tabNameByLangId;
        $orderState->color = $color;
        $orderState->logable = $isLogable;
        $orderState->paid = $isPaid;
        $orderState->invoice = $isInvoice;
        $orderState->shipped = $isShipped;
        $orderState->delivery = $isDelivery;
        $orderState->pdf_delivery = $isPdfDelivery;
        $orderState->pdf_invoice = $isPdfInvoice;
        $orderState->send_email = $isSendEmail;
        $orderState->hidden = $isHidden;
        $orderState->unremovable = $isUnremovable;
        $orderState->template = $template;
        $orderState->deleted = $isDeleted;
        $result = (bool)$orderState->add();

        if (false === $result) {
            $this->_errors[] = sprintf('Failed to create OrderState %s', $configurationKey);
            return false;
        }
        $result = (bool)Configuration::updateGlobalValue(
            $configurationKey,
            (int)$orderState->id
        );
        if (false === $result) {
            $this->_errors[] = sprintf('Failed to save OrderState %s to Configuration', $configurationKey);
            return false;
        }

        $orderStateImgPath = $this->getLocalPath() . 'views/img/orderstate/' . $configurationKey . '.png';
        if (false === (bool)Tools::file_exists_cache($orderStateImgPath)) {
            $this->_errors[] = sprintf(
                'Failed to find icon file of OrderState %s',
                $configurationKey
            );

            return false;
        }

        if (false === (bool)Tools::copy($orderStateImgPath, _PS_ORDER_STATE_IMG_DIR_ . $orderState->id . '.png')) {
            $this->_errors[] = sprintf(
                'Failed to copy icon of OrderState %s',
                $configurationKey
            );

            return false;
        }

        return true;
    }

    /**
     * @param $merchantOrderId
     * @return string
     */
    private function buildTiltUpRedirectUrl($merchantOrderId): string
    {
        $merchantId = Configuration::get(self::MERCHANT_ID_CONFIG);
        $shopId = Configuration::get(self::SHOP_ID_CONFIG);
        $env = Configuration::get(self::IS_STAGING_CONFIG) ? 'staging' : 'app';

        return 'https://payment.' . $env . '.tiltup.io/ecommerce/' . http_build_query([
                'merchantId' => $merchantId,
                'shopId' => $shopId,
                'merchantOrderId' => $merchantOrderId,
                'type' => self::TILTUP_ECOMMERCE_TYPE
            ]);
    }
}