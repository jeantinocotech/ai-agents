<x-app-layout>
    <div class="p-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">Estatísticas: {{ $agent->name }}</h2>
            <a href="{{ route('admin.dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                Voltar ao Dashboard
            </a>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('weeklyUsageChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($weeklyChartData['labels']) !!},
                    datasets: [{
                        label: 'Sessões de Chat',
                        data: {!! json_encode($weeklyChartData['sessions']) !!},
                        backgroundColor: 'rgba(79, 70, 229, 0.6)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                title: function(tooltipItems) {
                                    return 'Data: ' + tooltipItems[0].label;
                                },
                                label: function(context) {
                                    return context.parsed.y + ' sessões';
                                }
                            }
                        }
                    }
                }
            });
        });
        
    </script>
    @endpush
        
        <!-- Comentários e Avaliações -->
        <h3 class="text-xl font-bold mb-4">Avaliações e Comentários</h3>
        <div class="border rounded shadow bg-white mb-6">
            @if(count($ratings) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avaliação</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comentário</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($ratings as $rating)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $rating->user->name }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex text-yellow-500">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $rating->rating)
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                            @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                            </svg>
                                            @endif
                                        @endfor
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $rating->comment ?? 'Sem comentário' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $rating->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-4">
                    {{ $ratings->links() }}
                </div>
            @else
                <div class="p-6 text-center text-gray-500">
                    Nenhuma avaliação registrada para este agente.
                </div>
            @endif
        </div>

        <!-- Visão Geral -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="p-4 border rounded shadow bg-white">
                <p class="text-sm text-gray-500 mb-1">Total de Usuários</p>
                <p class="text-2xl font-bold text-blue-600">{{ $totalUsers }}</p>
            </div>
            
            <div class="p-4 border rounded shadow bg-white">
                <p class="text-sm text-gray-500 mb-1">Total de Sessões</p>
                <p class="text-2xl font-bold text-purple-600">{{ $totalSessions }}</p>
            </div>
            
            <div class="p-4 border rounded shadow bg-white">
                <p class="text-sm text-gray-500 mb-1">Média de Avaliação</p>
                <div class="flex items-center">
                    <p class="text-2xl font-bold text-yellow-500">{{ number_format($averageRating, 1) }}</p>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-500 ml-1" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                </div>
            </div>
            
            <div class="p-4 border rounded shadow bg-white">
                <p class="text-sm text-gray-500 mb-1">Status</p>
                @if($agent->is_active)
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Ativo</span>
                @else
                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">Inativo</span>
                @endif
            </div>
        </div>

        <!-- Detalhes do Agente -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="md:col-span-1">
                <div class="p-4 border rounded shadow bg-white h-full">
                    <h3 class="text-lg font-semibold mb-3">Informações do Agente</h3>
                    
                    <div class="mb-4 flex justify-center">
                        @if($agent->image_path)
                        <img src="{{ Storage::url($agent->image_path) }}" alt="{{ $agent->name }}" class="w-32 h-32 rounded-full object-cover">
                        @else
                        <div class="w-32 h-32 rounded-full bg-gray-200 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        @endif
                    </div>
                    
                    <div class="space-y-2">
                        <div>
                            <p class="text-sm text-gray-500">Nome</p>
                            <p>{{ $agent->name }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-500">Modelo</p>
                            <p>{{ $agent->model_type }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-500">Organização</p>
                            <p>{{ $agent->organization }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-500">Preço</p>
                            <p>R$ {{ number_format($agent->price, 2, ',', '.') }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-500">Data de Criação</p>
                            <p>{{ $agent->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
        
                </div>
            </div>
            
            <div class="p-4 border rounded shadow bg-white">
                <h3 class="text-lg font-semibold mb-3">Estatísticas de Conversas</h3>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 border rounded bg-gray-50">
                        <p class="text-sm text-gray-500">Usuários por Sessão</p>
                        <p class="text-xl font-bold">
                            {{ $totalSessions > 0 ? number_format($totalUsers / $totalSessions, 1) : 0 }}
                        </p>
                    </div>
                    
                    <div class="p-3 border rounded bg-gray-50">
                        <p class="text-sm text-gray-500">Sessões por Usuário</p>
                        <p class="text-xl font-bold">
                            {{ $totalUsers > 0 ? number_format($totalSessions / $totalUsers, 1) : 0 }}
                        </p>
                    </div>
                    
                    <div class="p-3 border rounded bg-gray-50">
                        <p class="text-sm text-gray-500">Taxa de Avaliação</p>
                        <p class="text-xl font-bold">
                            {{ $totalSessions > 0 ? number_format((array_sum($ratingDistribution) / $totalSessions) * 100, 1) : 0 }}%
                        </p>
                    </div>
                    
                    <div class="p-3 border rounded bg-gray-50">
                        <p class="text-sm text-gray-500">Ativos nos últimos 7 dias</p>
                        <p class="text-xl font-bold">
                            {{ array_sum($weeklyChartData['sessions']) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
                </div>
            </div>
            
            <div class="md:col-span-2">
                <div class="p-4 border rounded shadow bg-white h-full">
                    <h3 class="text-lg font-semibold mb-3">Uso Semanal</h3>
                    <canvas id="weeklyUsageChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Distribuição de Avaliações -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="p-4 border rounded shadow bg-white">
                <h3 class="text-lg font-semibold mb-3">Distribuição de Avaliações</h3>
                
                <div class="space-y-2">
                    @foreach(range(5, 1) as $rating)
                    <div class="flex items-center">
                        <div class="w-8 text-right mr-2">{{ $rating }}</div>
                        <div class="flex text-yellow-500">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $rating)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                                @endif
                            @endfor
                        </div>
                        
                        <div class="flex-1 ml-3">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                @php
                                    $totalRatings = array_sum($ratingDistribution);
                                    $percentage = $totalRatings > 0 ? ($ratingDistribution[$rating] / $totalRatings) * 100 : 0;
                                @endphp
                                <div class="bg-yellow-500 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                        
                        <div class="w-12 text-right ml-2">
                            {{ $ratingDistribution[$rating] }}
                        </div>