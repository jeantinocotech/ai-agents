<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\User;
use App\Models\Purchase;
use App\Models\PurchaseEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\AsaasService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Testimonial;

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
        $testimonials = Testimonial::where('is_approved', true)
        ->where('is_featured', true)
        ->inRandomOrder()
        ->limit(3)
        ->get();       
        
        $cart = session()->get('cart', []);
        //dd($testimonials);
        return view('cart.index', compact('cart','testimonials' ));
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
            // Salva para onde redirecionar depois do login
            session(['url.intended' => route('cart.checkout')]);
            return redirect()->route('cart.checkout.guest');
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

    public function checkoutGuest()
    {
        // Mostra tela amigável para login/cadastro
        return view('cart.checkout-guest');
    }

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
             'cep' => 'nullable|string|max:9',      // Novo campo
             'address' => 'nullable|string|max:255', // Novo campo
             'number' => 'nullable|string|max:10',   // Novo campo
             'city' => 'nullable|string|max:60',     // Novo campo
             'state' => 'nullable|string|max:2',     // Novo campo
             'payment_method' => 'required|in:credit_card,pix,boleto',
             'is_subscription' => 'sometimes|boolean',
             // Campos específicos do cartão de crédito
             'card_number' => 'required_if:payment_method,credit_card',
             'card_expiry' => 'required_if:payment_method,credit_card',
             'card_cvv' => 'required_if:payment_method,credit_card',
             'card_holder_name' => 'required_if:payment_method,credit_card',
         ]);

         if ($request->has('save_profile_data') && Auth::check()) {
            $user = Auth::user();
            $user->phone = $request->input('phone');
            $user->cpf = $request->input('document'); // Ou document, ajuste para o campo correto do seu banco
            $user->cep = $request->input('cep');
            $user->address = $request->input('address');
            $user->number = $request->input('number');
            $user->city = $request->input('city');
            $user->state = $request->input('state');
            $user->save();
        }
 
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
             $externalRef = "user_{$user->id}";
 
             // Primeiro, verifica se o cliente já existe no Asaas ou cria um novo
             $customerData = [
                 'name' => $request->name,
                 'email' => $request->email,
                 'phone' => $request->phone,
                 'cpfCnpj' => preg_replace('/[^0-9]/', '', $request->document),
                 'notificationDisabled' => false,
                 'externalReference' => $externalRef,
                 'postalCode' => preg_replace('/[^0-9]/', '', $request->cep),
                 'address'    => $request->address,
                 'addressNumber' => $request->number
             ];
             
             //$customer = $this->asaasService->findCustomerByEmail($request->email);
             
             $customer = $this->asaasService->findCustomerByExternalReference($externalRef);

             if (!$customer) {
                 log::info('Criando cliente no Asaas', [
                     'email' => $request->email,
                     'name' => $request->name,
                     'phone' => $request->phone,
                     'cpfCnpj' => $request->document,
                     'externalReference' => $externalRef
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
                         'description' =>   Str::limit("Assinatura do agente: {$agent->name}", 36),
                         'externalReference' => "user_{$user->id}_agent_{$agent->id}",
                         'cycle' => 'MONTHLY', // Ciclo mensal por padrão
                     ];

                     log::info('Subscripton:', [
                        'description 36' => $subscriptionData['description'],
                    ]);
                     
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
                         'description' =>   Str::limit("Compra do agente: {$agent->name}", 36),
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


                         // Armazena o primeiro pagamento bem-sucedido para uso posterior
                         $paymentData = $firstSuccessfulPayment;
                         
                         if ($paymentData) {
                         Log::info('First successful payment found and stored in paymentData variable.: ', [    
                          $paymentData,]);
                         }

                         if ($paymentData['success']) {
                            Log::info('Prepara QR Code PIX estático');

                             $agent = Agent::find($paymentData['agent_id']);

                             Log::info('Agent: ', [    
                                'agent' => $agent
                            ]);

                             // Prepara os dados para o QR code estático
                             $qrCodeData = [
                                 'addressKey' => $pixKey['key'],
                                 'description' => Str::limit("Compra do agente: {$agent->name}", 30),
                                 'value' => $agent->price,
                                 'format' => 'ALL',
                                 'expirationSeconds' => 3600, // 1 hora
                                 'allowsMultiplePayments' => false,
                                 'externalReference' => "user_{$user->id}_agent_{$agent->id}"
                             ];

                             Log::info('QR Code PIX estático antes de criar', [    
                                'qr_code' => $qrCodeData
                            ]);

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
                                     'results' => $results,
                                     'asaas_subscription_id' => $asaasResponse['id'] // <-- Adicione isso!
                                 ]);
                         } else {
                             Log::info('ERRO QR Code PIX estático', [    
                                 'qr_code' => $qrCodeData
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
            'asaas_subscription_id' => 'required|string',
        ]);


        $subscriptionId = $request->asaas_subscription_id;

        $paymentId = $request->payment_id;
        
        // Busca o status do pagamento no Asaas
        //$paymentInfo = $this->asaasService->getPayment($subscriptionId);

        $purchase = Purchase::where('asaas_subscription_id', $subscriptionId)->first();
        
        if ($purchase && $purchase->active) {
            session()->forget('cart');
            return response()->json([
                'success' => true,
                'is_paid' => true,
            ]);
        }
        
        return response()->json([
            'success' => true,
            'is_paid' => false,
        ]);
   
    }
}
