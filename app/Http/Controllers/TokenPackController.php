<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TokenPackOrder;
use App\Rules\ValidBrazilTaxId;
use App\Services\AsaasService;
use App\Services\TokenPackOrderCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TokenPackController extends Controller
{
    public function __construct(
        private AsaasService $asaasService
    ) {}

    public function show(): View|RedirectResponse
    {
        $user = Auth::user();
        if ($user !== null && ! $user->hasVerifiedEmail()) {
            return redirect()
                ->route('verification.notice')
                ->with('warning', 'Confirme o seu e-mail antes de comprar tokens.');
        }

        $tokensAmount = (int) Setting::get('token_pack_amount', 10000);
        $price = (float) Setting::get('token_pack_price', 49.90);

        return view('tokens.purchase', compact('user', 'tokensAmount', 'price'));
    }

    public function process(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasVerifiedEmail(), 403, 'Confirme o seu e-mail.');

        $request->merge([
            'state' => strtoupper((string) $request->input('state')),
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:20'],
            'document' => ['required', 'string', new ValidBrazilTaxId],
            'cep' => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'address' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:10'],
            'city' => ['required', 'string', 'max:60'],
            'state' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
            'payment_method' => ['required', 'in:credit_card,pix,boleto'],
            'card_number' => ['required_if:payment_method,credit_card', 'nullable', 'string'],
            'card_expiry' => ['required_if:payment_method,credit_card', 'nullable', 'string'],
            'card_cvv' => ['required_if:payment_method,credit_card', 'nullable', 'string'],
            'card_holder_name' => ['required_if:payment_method,credit_card', 'nullable', 'string'],
        ]);

        $tokensPerPack = (int) Setting::get('token_pack_amount', 0);
        $pricePerPack = (float) Setting::get('token_pack_price', 0);
        if ($tokensPerPack < 1 || $pricePerPack <= 0) {
            return response()->json(['success' => false, 'error' => 'Pacote de tokens não configurado.'], 400);
        }

        $qty = max(1, min(10, (int) ($request->input('quantity') ?? 1)));
        $tokensAmount = $tokensPerPack * $qty;
        $price = round($pricePerPack * $qty, 2);

        if ($request->boolean('save_profile_data')) {
            $u = Auth::user();
            $u->phone = $request->input('phone');
            $u->cpf = $request->input('document');
            $u->cep = $request->input('cep');
            $u->address = $request->input('address');
            $u->number = $request->input('number');
            $u->city = $request->input('city');
            $u->state = $request->input('state');
            $u->save();
        }

        $user = Auth::user();

        try {
            return DB::transaction(function () use ($request, $user, $tokensAmount, $price) {
                $order = TokenPackOrder::query()->create([
                    'user_id' => $user->id,
                    'tokens_amount' => $tokensAmount,
                    'amount_brl' => $price,
                    'status' => TokenPackOrder::STATUS_PENDING,
                ]);

                $externalRef = "user_{$user->id}";
                $customer = $this->asaasService->findCustomerByExternalReference($externalRef);

                $customerData = [
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'cpfCnpj' => preg_replace('/[^0-9]/', '', $request->document),
                    'notificationDisabled' => false,
                    'externalReference' => $externalRef,
                    'postalCode' => preg_replace('/[^0-9]/', '', (string) $request->cep),
                    'address' => $request->address,
                    'addressNumber' => $request->number,
                ];

                if (! $customer) {
                    $customerResponse = $this->asaasService->createCustomer($customerData);
                    if (! $customerResponse) {
                        throw new \RuntimeException('Erro ao criar cliente no Asaas');
                    }
                    $customerId = $customerResponse['id'];
                } else {
                    $customerId = $customer['id'];
                }

                $billingType = $this->mapPaymentMethod($request->payment_method);

                $externalPayload = json_encode([
                    'type' => 'token_pack',
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ]);

                $paymentData = [
                    'customer' => $customerId,
                    'billingType' => $billingType,
                    'value' => $price,
                    'dueDate' => date('Y-m-d', strtotime('+1 day')),
                    'description' => Str::limit('Pacote de tokens GratoAI', 36),
                    'externalReference' => $externalPayload,
                ];

                if ($request->payment_method === 'credit_card') {
                    $paymentData['creditCard'] = [
                        'holderName' => $request->card_holder_name,
                        'number' => str_replace(' ', '', (string) $request->card_number),
                        'expiryMonth' => explode('/', (string) $request->card_expiry)[0],
                        'expiryYear' => '20'.explode('/', (string) $request->card_expiry)[1],
                        'ccv' => $request->card_cvv,
                    ];
                    $paymentData['creditCardHolderInfo'] = [
                        'name' => $request->card_holder_name,
                        'email' => $request->email,
                        'cpfCnpj' => preg_replace('/[^0-9]/', '', $request->document),
                        'phone' => $request->phone,
                    ];
                }

                $asaasResponse = $this->asaasService->createPayment($paymentData);
                if (! $asaasResponse || empty($asaasResponse['id'])) {
                    throw new \RuntimeException('Erro ao criar pagamento no Asaas');
                }

                $order->asaas_payment_id = $asaasResponse['id'];
                $order->save();

                if ($user->asaas_customer_id !== $customerId) {
                    $user->asaas_customer_id = $customerId;
                    $user->save();
                }

                if ($request->payment_method === 'pix') {
                    $pixInfo = $this->asaasService->getPixQrCode($asaasResponse['id']);

                    return response()->json([
                        'success' => true,
                        'is_pix' => true,
                        'pix_info' => $pixInfo,
                        'payment_id' => $asaasResponse['id'],
                        'asaas_subscription_id' => $asaasResponse['id'],
                        'invoice_url' => $asaasResponse['invoiceUrl'] ?? null,
                    ]);
                }

                $payUrl = $asaasResponse['invoiceUrl']
                    ?? $asaasResponse['bankSlipUrl']
                    ?? null;

                $checkoutButtonLabel = match ($request->payment_method) {
                    'boleto' => 'Abrir boleto / fatura no Asaas',
                    'credit_card' => 'Concluir pagamento no Asaas',
                    default => 'Abrir página de pagamento no Asaas',
                };

                return response()->json([
                    'success' => true,
                    'invoice_url' => $payUrl,
                    'payment_id' => $asaasResponse['id'],
                    'checkout_button_label' => $payUrl ? $checkoutButtonLabel : null,
                    'checkout_hint' => $payUrl
                        ? 'O Asaas abre em novo separador. Pode fechá-lo depois — esta página passa a verificar o pagamento automaticamente até creditar os tokens.'
                        : null,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Token pack checkout failed', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'exception_class' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $payload = [
                'success' => false,
                'error' => 'Não foi possível iniciar o pagamento. Tente novamente.',
            ];
            if (config('app.debug')) {
                $payload['debug_detail'] = $e->getMessage();
            }

            return response()->json($payload, 500);
        }
    }

    public function checkPaymentStatus(Request $request): JsonResponse
    {
        $request->validate([
            'payment_id' => ['required', 'string'],
        ]);

        $user = Auth::user();
        if ($user === null) {
            return response()->json(['success' => false, 'is_paid' => false], 401);
        }

        $payment = $this->asaasService->getPayment($request->payment_id);
        if (! $payment) {
            return response()->json(['success' => false, 'is_paid' => false]);
        }

        $meta = json_decode((string) ($payment['externalReference'] ?? ''), true);
        $ownsTokenPack = is_array($meta)
            && ($meta['type'] ?? '') === 'token_pack'
            && (int) ($meta['user_id'] ?? 0) === (int) $user->id;

        $status = $payment['status'] ?? '';
        $isPaid = in_array($status, ['RECEIVED', 'CONFIRMED'], true);

        /** Mesma lógica do webhook — útil quando o webhook atrasa ou não chega ao servidor. */
        if ($isPaid && $ownsTokenPack) {
            app(TokenPackOrderCompletionService::class)->tryCompleteFromAsaasPayment($payment);
        }

        return response()->json([
            'success' => true,
            'is_paid' => $isPaid,
        ]);
    }

    public function history(): View|RedirectResponse
    {
        $user = Auth::user();
        if ($user !== null && ! $user->hasVerifiedEmail()) {
            return redirect()
                ->route('verification.notice')
                ->with('warning', 'Confirme o seu e-mail para ver o histórico.');
        }

        $orders = TokenPackOrder::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('id')
            ->paginate(20);

        return view('tokens.history', compact('orders'));
    }

    private function mapPaymentMethod(string $method): string
    {
        return match ($method) {
            'credit_card' => 'CREDIT_CARD',
            'pix' => 'PIX',
            'boleto' => 'BOLETO',
            default => 'UNDEFINED',
        };
    }
}
