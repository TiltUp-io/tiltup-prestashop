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
class TiltUpCryptoPaymentsModuleErrorModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            'error' => Tools::getValue('error'),
        ]);
        $this->setTemplate('module:tiltupcryptopaymentsmodule/views/templates/front/error.tpl');
    }
}
