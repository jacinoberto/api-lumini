<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\UseCases\ProcessPaymentWebhookUseCase;
use App\Infrastructure\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly ProcessPaymentWebhookUseCase $processWebhook,
        private readonly MercadoPagoService           $mercadoPago,
    ) {}

    public function mercadopago(Request $request): JsonResponse
    {
        $topic = $request->query('topic') ?? $request->input('type');
        $id    = $request->query('id') ?? $request->input('data.id');

        Log::info('Webhook MP recebido', ['topic' => $topic, 'id' => $id, 'body' => $request->all()]);

        if ($topic === 'merchant_order') {
            return $this->handleMerchantOrder((string) $id);
        }

        // MP envia dois formatos: topic=payment ou type=payment
        if (!in_array($topic, ['payment', 'payment_methods'])) {
            return response()->json(['message' => 'Tópico ignorado.'], 200);
        }

        if (!$id) {
            return response()->json(['message' => 'ID do pagamento ausente.'], 422);
        }

        try {
            $this->processWebhook->execute((string) $id);
        } catch (\Throwable $e) {
            Log::error('Erro ao processar webhook MP', ['error' => $e->getMessage()]);
            // Retorna 200 para o MP não reenviar indefinidamente
            return response()->json(['message' => 'Erro interno, verifique os logs.'], 200);
        }

        return response()->json(['message' => 'ok'], 200);
    }

    private function handleMerchantOrder(string $orderId): JsonResponse
    {
        try {
            $order = $this->mercadoPago->getMerchantOrder($orderId);

            foreach ($order->payments ?? [] as $payment) {
                if ($payment->status === 'approved') {
                    $this->processWebhook->execute((string) $payment->id);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao processar merchant_order MP', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
        }

        return response()->json(['message' => 'ok'], 200);
    }
}
