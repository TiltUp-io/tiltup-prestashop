<?php

require_once __DIR__ . '/TiltUpFrontController.php';

class TiltUpCryptoPaymentsModuleWebhookModuleFrontController extends TiltUpFrontController
{
    protected function getHmac(): string
    {
        $headers = array_change_key_case(getallheaders());

        return $headers['tiltup-hmac'] ?? '';
    }

    protected function handleInvalidToken(): void
    {
        header('HTTP/1.1 401 Unauthorized', true, 401);

        exit;
    }


    protected function handleRequest(Order $order)
    {
        $this->updateOrderState($order, (int)Configuration::getGlobalValue(TiltUpCryptoPaymentsModule::CRYPTO_PAYMENT_COMPLETED_STATUS_CONFIG));
        header('HTTP/1.1 200 OK', true, 200);

        exit;
    }

    protected function handleOrderNotFound(): void
    {
        header('HTTP/1.1 404 Not Found', true, 404);

        exit;
    }
}
