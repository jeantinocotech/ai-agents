<x-app-layout>
    <div class="max-w-4xl mx-auto py-12 px-6">
        <div class="text-center">
            <!-- Ícone de sucesso -->
            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-3xl font-bold text-gray-900 mb-4">Compra realizada com sucesso!</h1>
            
            <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
                <p class="text-lg text-gray-600 mb-6">
                    Parabéns! Sua compra foi processada com sucesso. 
                </p>
                
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-left">
                            <h3 class="text-green-900 font-medium mb-2">Próximos passos:</h3>
                            <ul class="text-green-800 text-sm space-y-1">
                                <li>• Seu acesso será liberado automaticamente em alguns minutos</li>
                                <li>• Você receberá um email de confirmação da Hotmart</li>
                                <li>• Os agentes comprados aparecerão no seu dashboard</li>
                                <li>• Em caso de dúvidas, entre em contato com nosso suporte</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-blue-800 text-sm">
                            <strong>Liberação do acesso:</strong> O processo pode levar até 15 minutos após a confirmação do pagamento.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Botões de ação -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Ir para o Dashboard
                </a>

                <a href="{{ route('agents.index') }}" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Ver Meus Agentes
                </a>

                <a href="{{ route('home') }}" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Página Inicial
                </a>
            </div>

            <!-- Link de suporte -->
            <div class="mt-8 pt-8 border-t">
                <p class="text-gray-600 text-sm">
                    Problemas com sua compra? 
                    <a href="mailto:suporte@seusite.com" class="text-blue-600 hover:text-blue-800 underline">
                        Entre em contato com o suporte
                    </a>
                </p>
            </div>
        </div>
    </div>
</x-app-layout>