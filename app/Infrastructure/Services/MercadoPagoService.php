<?php

namespace App\Infrastructure\Services;

use Illuminate\Support\Facades\Log;
use MercadoPago\Client\MerchantOrder\MerchantOrderClient;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Payment\PaymentRefundClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Preference;

class MercadoPagoService
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

        if (app()->environment('local')) {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
        }
    }

    public function createPreference(array $data): Preference
    {
        $client = new PreferenceClient();

        $payload = [
            'items' => [
                [
                    'id'          => $data['appointment_id'],
                    'title'       => $data['service_name'] . ' - ' . $data['barbershop_name'],
                    'quantity'    => 1,
                    'unit_price'  => (float) $data['amount'],
                    'currency_id' => 'BRL',
                ],
            ],
            'payer' => [
                'name'  => $data['client_name'],
                'email' => $data['client_email'],
            ],
            'back_urls' => [
                'success' => config('services.mercadopago.back_url_success'),
                'failure' => config('services.mercadopago.back_url_failure'),
                'pending' => config('services.mercadopago.back_url_pending'),
            ],
            'notification_url'     => config('services.mercadopago.notification_url'),
            'external_reference'   => $data['appointment_id'],
            'statement_descriptor' => 'Lumini',
        ];

        Log::info('MP createPreference payload', $payload);

        return $client->create($payload);
    }

    public function getPayment(string $mpPaymentId): array
    {
        $client = new PaymentClient();
        $payment = $client->get((int) $mpPaymentId);

        return (array) $payment;
    }

    public function getMerchantOrder(string $orderId): object
    {
        $client = new MerchantOrderClient();
        return $client->get((int) $orderId);
    }

    public function refund(string $mpPaymentId): array
    {
        $client = new PaymentRefundClient();
        $refund = $client->refundTotal((int) $mpPaymentId);

        return (array) $refund;
    }
}
