<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\Purchase;
use App\Models\PurchaseEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\AsaasService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartController extends Controller
{

    protected $asaasService;

    public function __construct(AsaasService $asaasService)
    {
        $this->asaasService = $asaasService;
    }

    // Exibe o carrinho
    public function index()
    {
        $cart = session()->get('cart', []);
        return view('cart.index', compact('cart'));
    }

    // Adiciona agente ao carrinho
    public function addToCart($id)
    {
        $agent = Agent::findOrFail($id);

        // Verifica se o usuário já tem acesso ao agente
        if (Auth::check()) {
            $existingPurchase = Purchase::where('user_id', Auth::id())
                ->where('agent_id', $agent->id)
                ->where('active', true)
                ->first();

            if ($existingPurchase) {
                return redirect()->route('agents.show', $agent)
                    ->with('info', 'Você já tem acesso a este agente.');
            }
        }

        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            return redirect()->route('cart.index')->with('info', 'Agente já está no carrinho.');
        }

        $cart[$id] = [
            'id' => $agent->id,
            'name' => $agent->name,
            'price' => $agent->price,
            'description' => $agent->description,
        ];

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Agente adicionado ao carrinho.');
    }

    // Remove agente do carrinho
    public function removeFromCart($id)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Agente removido do carrinho.');
    }

    // Limpa o carrinho inteiro
    public function clearCart()
    {
        session()->forget('cart');
        return redirect()->route('cart.index')->with('success', 'Carrinho limpo com sucesso.');
    }

    // Página de checkout (precisa estar logado)
    // Página de checkout
    public function checkout()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('message', 'Você precisa estar logado para finalizar a compra.');
        }

        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Seu carrinho está vazio.');
        }

        // Busca os agentes completos do banco
        $agentIds = array_keys($cart);
        $agents = Agent::whereIn('id', $agentIds)->get();

        $total = array_sum(array_column($cart, 'price'));
        
        $user = Auth::user();
        
        Log::info('Checkout iniciado', [
            'user_id' => $user->id,
            'agents' => $agentIds,
            'total' => $total
        ]);

        return view('cart.check-improved', compact('agents', 'total', 'user'));
    }

    // Página de sucesso após checkout
     // Página de sucesso após checkout
    public function checkoutSuccess()
    {
        // Limpa o carrinho após sucesso
        session()->forget('cart');
        return view('cart.success');
    }

     // Processa o checkout via API da Asaas
     public function processCheckout(Request $request)
     {
         if (!Auth::check()) {
             return response()->json(['error' => 'Usuário não autenticado'], 401);
         }
 
         $request->validate([
             'name' => 'required|string|max:255',
             'email' => 'required|email',
             'phone' => 'required|string|max:20',
             'document' => 'required|string|max:14',
             'payment_method' => 'required|in:credit_card,pix,boleto',
             'is_subscription' => 'sometimes|boolean',
             // Campos específicos do cartão de crédito
             'card_number' => 'required_if:payment_method,credit_card',
             'card_expiry' => 'required_if:payment_method,credit_card',
             'card_cvv' => 'required_if:payment_method,credit_card',
             'card_holder_name' => 'required_if:payment_method,credit_card',
         ]);
 
         $cart = session()->get('cart', []);
         if (empty($cart)) {
             return response()->json(['error' => 'Carrinho está vazio'], 400);
         }
 
         $user = Auth::user();
         $agentIds = array_keys($cart);
         $agents = Agent::whereIn('id', $agentIds)->get();
         $isSubscription = true; // Sempre usar assinatura, independente do valor enviado no request
 
         try {
             DB::beginTransaction();
 
             $results = [];
 
             // Primeiro, verifica se o cliente já existe no Asaas ou cria um novo
             $customerData = [
                 'name' => $request->name,
                 'email' => $request->email,
                 'phone' => $request->phone,
                 'cpfCnpj' => preg_replace('/[^0-9]/', '', $request->document),
                 'notificationDisabled' => false,
             ];
             
             $customer = $this->asaasService->findCustomerByEmail($request->email);
             
             if (!$customer) {
                 log::info('Criando cliente no Asaas', [
                     'email' => $request->email,
                     'name' => $request->name,
                     'phone' => $request->phone,
                     'cpfCnpj' => $request->document,
                 ]);
                 $customerResponse = $this->asaasService->createCustomer($customerData);
                 if (!$customerResponse) {
                     throw new \Exception('Erro ao criar cliente no Asaas');
                 }
                 $customerId = $customerResponse['id'];
             } else {
                 $customerId = $customer['id'];
             }
 
             foreach ($agents as $agent) {
                 
                 // Prepara metadados para rastreamento
                 $metadata = [
                     'user_id' => $user->id,
                     'agent_id' => $agent->id,
                 ];
                 
                 // Mapeia o método de pagamento para o formato do Asaas
                 $billingType = $this->mapPaymentMethod($request->payment_method);
                 
                 // Verifica se é uma assinatura ou pagamento único
                 if ($isSubscription) {
                     // Prepara dados da assinatura para o Asaas
                     $subscriptionData = [
                         'customer' => $customerId,
                         'billingType' => $billingType,
                         'value' => $agent->price,
                         'nextDueDate' => date('Y-m-d', strtotime('+1 day')),
                         'description' => "Assinatura do agente: {$agent->name}",
                         'externalReference' => "user_{$user->id}_agent_{$agent->id}",
                         'cycle' => 'MONTHLY', // Ciclo mensal por padrão
                     ];
                     
                     // Adiciona dados do cartão se for pagamento com cartão
                     if ($request->payment_method === 'credit_card') {
                         $subscriptionData['creditCard'] = [
                             'holderName' => $request->card_holder_name,
                             'number' => str_replace(' ', '', $request->card_number),
                             'expiryMonth' => explode('/', $request->card_expiry)[0],
                             'expiryYear' => '20' . explode('/', $request->card_expiry)[1],
                             'ccv' => $request->card_cvv,
                         ];
                         
                         // Adiciona dados do titular do cartão
                         $subscriptionData['creditCardHolderInfo'] = [
                             'name' => $request->card_holder_name,
                             'email' => $request->email,
                             'cpfCnpj' => preg_replace('/[^0-9]/', '', $request->document),
                             'phone' => $request->phone,
                         ];
                     }
                     
                     // Cria a assinatura no Asaas

                     Log::info('Asaas subscription request :', [$subscriptionData]);

                     $asaasResponse = $this->asaasService->createSubscription($subscriptionData);
                     
                     Log::info('Asaas subscription response:', [$asaasResponse ?? 'Nenhuma resposta recebida']);
                     
                     // Verifica se a resposta do Asaas foi bem-sucedida
                     if ($asaasResponse) {
                         // Cria ou atualiza a purchase local
                         $purchase = Purchase::updateOrCreate([
                             'user_id' => $user->id,
                             'agent_id' => $agent->id,
                         ], [
                             'active' => false, // Será ativado pelo webhook
                             'paused' => false,
                             'paused_at' => null,
                             'asaas_subscription_id' => $asaasResponse['id'],
                         ]);
                         
                         // Registra o evento
                         PurchaseEvent::create([
                             'purchase_id' => $purchase->id,
                             'event_type' => 'subscription_created',
                             'event_time' => now(),
                             'note' => 'Assinatura criada via API do Asaas',
                             'metadata' => json_encode($asaasResponse)
                         ]);
                         
                         $results[] = [
                             'agent_id' => $agent->id,
                             'agent_name' => $agent->name,
                             'success' => true,
                             'asaas_data' => $asaasResponse,
                             'type' => 'subscription'
                         ];
                         
                         Log::info('Assinatura criada no Asaas', [
                             'agent_id' => $agent->id,
                             'user_id' => $user->id,
                             'asaas_response' => $asaasResponse
                         ]);
                     } else {
                         $results[] = [
                             'agent_id' => $agent->id,
                             'agent_name' => $agent->name,
                             'success' => false,
                             'error' => 'Erro ao criar assinatura no Asaas',
                             'type' => 'subscription'
                         ];
                     }
                 } else {
                     // Prepara dados do pagamento para o Asaas (pagamento único)
                     $paymentData = [
                         'customer' => $customerId,
                         'billingType' => $billingType,
                         'value' => $agent->price,
                         'dueDate' => date('Y-m-d', strtotime('+1 day')),
                         'description' => "Compra do agente: {$agent->name}",
                         'externalReference' => json_encode($metadata),
                     ];
                     
                     // Adiciona dados do cartão se for pagamento com cartão
                     if ($request->payment_method === 'credit_card') {
                         $paymentData['creditCard'] = [
                             'holderName' => $request->card_holder_name,
                             'number' => str_replace(' ', '', $request->card_number),
                             'expiryMonth' => explode('/', $request->card_expiry)[0],
                             'expiryYear' => '20' . explode('/', $request->card_expiry)[1],
                             'ccv' => $request->card_cvv,
                         ];
                         
                         // Adiciona dados do titular do cartão
                         $paymentData['creditCardHolderInfo'] = [
                             'name' => $request->card_holder_name,
                             'email' => $request->email,
                             'cpfCnpj' => preg_replace('/[^0-9]/', '', $request->document),
                             'phone' => $request->phone,
                         ];
                     }
                     
                     // Cria o pagamento no Asaas
                     $asaasResponse = $this->asaasService->createPayment($paymentData);
                     
                     Log::info('Asaas payment response:', [$asaasResponse ?? 'Nenhuma resposta recebida']);
                     
                     // Verifica se a resposta do Asaas foi bem-sucedida
                     if ($asaasResponse) {
                         // Cria ou atualiza a purchase local
                         $purchase = Purchase::updateOrCreate([
                             'user_id' => $user->id,
                             'agent_id' => $agent->id,
                         ], [
                             'active' => false, // Será ativado pelo webhook
                             'paused' => false,
                             'paused_at' => null,
                             'asaas_subscription_id' => null, // Não é uma assinatura neste caso
                         ]);
                         
                         // Registra o evento
                         PurchaseEvent::create([
                             'purchase_id' => $purchase->id,
                             'event_type' => 'payment_created',
                             'event_time' => now(),
                             'note' => 'Pagamento criado via API do Asaas',
                             'metadata' => json_encode($asaasResponse)
                         ]);
                         
                         $results[] = [
                             'agent_id' => $agent->id,
                             'agent_name' => $agent->name,
                             'success' => true,
                             'asaas_data' => $asaasResponse,
                             'type' => 'payment'
                         ];
                         
                         Log::info('Pagamento criado no Asaas', [
                             'agent_id' => $agent->id,
                             'user_id' => $user->id,
                             'asaas_response' => $asaasResponse
                         ]);
                     } else {
                         $results[] = [
                             'agent_id' => $agent->id,
                             'agent_name' => $agent->name,
                             'success' => false,
                             'error' => 'Erro ao criar pagamento no Asaas',
                             'type' => 'payment'
                         ];
                     }
                 }
             }
 
             DB::commit();
 
             // Para pagamentos PIX, gera um QR code estático
             if ($request->payment_method === 'pix' && !empty($results)) {
                 try {
                     // 1. Verifica se existe uma chave PIX
                     $pixKeys = $this->asaasService->listPixKeys();
                     $pixKey = null;
                     
                     if ($pixKeys && isset($pixKeys['data']) && !empty($pixKeys['data'])) {
                         // Procura por uma chave ativa
                         foreach ($pixKeys['data'] as $key) {
                             if ($key['status'] === 'ACTIVE') {
                                 $pixKey = $key;
                                 break;
                             }
                         }
                     }
                     
                     // Se não encontrou uma chave ativa, cria uma nova
                     if (!$pixKey) {
                         $newKey = $this->asaasService->createPixKey();
                         if ($newKey && isset($newKey['key'])) {
                             $pixKey = $newKey;
                         } else {
                             throw new \Exception('Não foi possível criar uma chave PIX');
                         }
                     }
                     
                     // 2. Cria um QR code estático para o pagamento
                     if ($pixKey && isset($pixKey['key'])) {
                         // Encontra o primeiro pagamento bem-sucedido para obter os detalhes
                         $firstSuccessfulPayment = null;
                         foreach ($results as $result) {
                             if ($result['success']) {
                                 $firstSuccessfulPayment = $result;
                                 break;
                             }
                         }
                         
                         if ($firstSuccessfulPayment) {
                             $agent = Agent::find($firstSuccessfulPayment['agent_id']);
                             
                             // Prepara os dados para o QR code estático
                             $qrCodeData = [
                                 'addressKey' => $pixKey['key'],
                                 'description' => Str::limit("Compra do agente: {$agent->name}", 37),
                                 'value' => $agent->price,
                                 'format' => 'ALL',
                                 'expirationSeconds' => 3600, // 1 hora
                                 'allowsMultiplePayments' => false,
                                 'externalReference' => "user_{$user->id}_agent_{$agent->id}"
                             ];
                             
                             $pixQrCode = $this->asaasService->createStaticPixQrCode($qrCodeData);
                             
                             if ($pixQrCode) {
                                 Log::info('QR Code PIX estático gerado com sucesso', [
                                     'user_id' => $user->id,
                                     'agent_id' => $agent->id,
                                     'qr_code' => $pixQrCode
                                 ]);
                                 
                                 return response()->json([
                                     'success' => true,
                                     'message' => 'Pagamento PIX gerado com sucesso',
                                     'is_pix' => true,
                                     'pix_info' => $pixQrCode,
                                     'results' => $results
                                 ]);
                             }
                         }
                     }
                 } catch (\Exception $e) {
                     Log::error('Erro ao gerar QR Code PIX', [
                         'error' => $e->getMessage(),
                         'user_id' => $user->id
                     ]);
                 }
             }
             
             // Para outros métodos de pagamento, limpa o carrinho e retorna sucesso
             session()->forget('cart');
 
             return response()->json([
                 'success' => true,
                 'message' => 'Pagamentos processados com sucesso',
                 'results' => $results
             ]);
 
         } catch (\Exception $e) {
             DB::rollBack();
             
             Log::error('Erro ao processar checkout', [
                 'error' => $e->getMessage(),
                 'user_id' => $user->id,
                 'agents' => $agentIds
             ]);
 
             return response()->json([
                 'success' => false,
                 'error' => 'Erro interno. Tente novamente.'
             ], 500);
         }
     }
     
     
     /**
      * Mapeia os métodos de pagamento do frontend para os tipos aceitos pelo Asaas
      */
     private function mapPaymentMethod(string $method): string
     {
         $mapping = [
             'credit_card' => 'CREDIT_CARD',
             'pix' => 'PIX',
             'boleto' => 'BOLETO',
         ];
         
         return $mapping[$method] ?? 'UNDEFINED';
     }

    /**
     * Verifica o status de um pagamento
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPaymentStatus(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|string',
        ]);

        $paymentId = $request->payment_id;
        
        // Busca o status do pagamento no Asaas
        $paymentInfo = $this->asaasService->getPayment($paymentId);
        
        if (!$paymentInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível obter informações do pagamento',
            ], 404);
        }
        
        $status = $paymentInfo['status'] ?? 'UNKNOWN';
        $isPaid = in_array($status, ['CONFIRMED', 'RECEIVED']);
        
        // Se o pagamento foi confirmado, limpa o carrinho
        if ($isPaid) {
            session()->forget('cart');
        }
        
        return response()->json([
            'success' => true,
            'payment_id' => $paymentId,
            'status' => $status,
            'is_paid' => $isPaid,
            'payment_info' => $paymentInfo,
        ]);
    }
}
