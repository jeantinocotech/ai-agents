<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\Purchase;
use App\Models\PurchaseEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\HotmartService;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{

    protected $hotmartService;

    public function __construct(HotmartService $hotmartService)
    {
        $this->hotmartService = $hotmartService;
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
    // Página de checkout - agora direcionando para Hotmart
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
        
       // Verifica se todos os agentes têm produto na Hotmart configurado
       $agentsWithoutProduct = $agents->whereNull('hotmart_product_id');
       if ($agentsWithoutProduct->isNotEmpty()) {
           return redirect()->route('cart.index')->with('error', 'Alguns agentes não têm produto configurado na Hotmart.');
       }

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

     // Processa o checkout via API da Hotmart
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
 
         try {
             DB::beginTransaction();
 
             $results = [];
 
             foreach ($agents as $agent) {
                 // Prepara dados do pedido para a Hotmart
                 $orderData = [
                     'buyer' => [
                         'name' => $request->name,
                         'email' => $request->email,
                         'phone' => $request->phone,
                         'document' => $request->document,
                     ],
                     'product' => [
                         'id' => $agent->hotmart_product_id,
                     ],
                     'payment' => [
                         'type' => $request->payment_method,
                     ],
                     'offers' => [
                         [
                             'key' => 'default',
                             'options' => [
                                 'installments' => 1
                             ]
                         ]
                     ]
                 ];
 
                 // Adiciona dados do cartão se for pagamento com cartão
                 if ($request->payment_method === 'credit_card') {
                     $orderData['payment']['card'] = [
                         'number' => str_replace(' ', '', $request->card_number),
                         'expiry_month' => explode('/', $request->card_expiry)[0],
                         'expiry_year' => '20' . explode('/', $request->card_expiry)[1],
                         'cvv' => $request->card_cvv,
                         'holder_name' => $request->card_holder_name,
                     ];
                 }
 
                 // Cria o pedido na Hotmart
                 $hotmartResponse = $this->hotmartService->createOrder($orderData);

                 log::info('Hotmart response:', [$hotmartResponse ?? 'Nenhuma resposta recebida']);
 
                 // Verifica se a resposta da Hotmart foi bem-sucedida
 
                 if ($hotmartResponse) {

                    log::info('Hotmart response true',[$hotmartResponse ?? 'Nenhuma resposta recebida']);
                    
                     // Cria ou atualiza a purchase local
                     $purchase = Purchase::updateOrCreate([
                         'user_id' => $user->id,
                         'agent_id' => $agent->id,
                     ], [
                         'active' => false, // Será ativado pelo webhook
                         'paused' => false,
                         'paused_at' => null,
                         'hotmart_subscription_code' => $hotmartResponse['subscription']['code'] ?? null,
                     ]);
 
                     // Registra o evento
                     PurchaseEvent::create([
                         'purchase_id' => $purchase->id,
                         'event_type' => 'order_created',
                         'event_time' => now(),
                         'note' => 'Pedido criado via API da Hotmart',
                         'metadata' => json_encode($hotmartResponse)
                     ]);
 
                     $results[] = [
                         'agent_id' => $agent->id,
                         'agent_name' => $agent->name,
                         'success' => true,
                         'hotmart_data' => $hotmartResponse
                     ];
 
                     Log::info('Pedido criado na Hotmart', [
                         'agent_id' => $agent->id,
                         'user_id' => $user->id,
                         'hotmart_response' => $hotmartResponse
                     ]);
                 } else {
                     $results[] = [
                         'agent_id' => $agent->id,
                         'agent_name' => $agent->name,
                         'success' => false,
                         'error' => 'Erro ao criar pedido na Hotmart'
                     ];
                 }
             }
 
             DB::commit();
 
             // Limpa o carrinho após o processamento
             session()->forget('cart');
 
             return response()->json([
                 'success' => true,
                 'message' => 'Pedidos processados com sucesso',
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

     // Método para sincronizar preços (pode ser chamado por um comando/job)
    public function syncPrices()
    {
        $results = $this->hotmartService->syncAllProductPrices();
        
        Log::info('Sincronização de preços executada', ['results' => $results]);
        
        return response()->json([
            'success' => true,
            'message' => 'Preços sincronizados com sucesso',
            'results' => $results
        ]);
    }

     // Método para sincronizar preços da Hotmart para o sistema
     public function syncPricesFromHotmart()
     {
         $results = $this->hotmartService->syncPricesFromHotmart();
         
         Log::info('Sincronização de preços da Hotmart executada', ['results' => $results]);
         
         return response()->json([
             'success' => true,
             'message' => 'Preços sincronizados da Hotmart com sucesso',
             'results' => $results
         ]);
     }

}
