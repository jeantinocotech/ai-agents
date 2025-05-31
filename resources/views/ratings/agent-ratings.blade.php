
<x-app-layout>
<div class="bg-white rounded-lg shadow-md p-6 mt-6">
    <h3 class="text-xl font-semibold text-gray-900 mb-4">Avaliações dos Usuários</h3>
    
    <!-- Resumo das Avaliações -->
    <div class="flex items-center mb-6 p-4 bg-gray-50 rounded-lg">
        <div class="flex-1">
            <div class="flex items-center mb-2">
                <span class="text-3xl font-bold text-gray-900 mr-2">{{ number_format($averageRating, 1) }}</span>
                <div class="flex text-yellow-400">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= round($averageRating))
                            <svg class="h-6 w-6 fill-current" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @else
                            <svg class="h-6 w-6 text-gray-300 fill-current" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endif
                    @endfor
                </div>
            </div>
            <p class="text-sm text-gray-600">Baseado em {{ $totalRatings }} avaliações</p>
        </div>
        
        <!-- Distribuição das Avaliações -->
        <div class="ml-8 space-y-1">
            @for($star = 5; $star >= 1; $star--)
                @php
                    $count = $ratingDistribution[$star] ?? 0;
                    $percentage = $totalRatings > 0 ? ($count / $totalRatings) * 100 : 0;
                @endphp
                <div class="flex items-center text-sm">
                    <span class="w-3">{{ $star }}</span>
                    <svg class="h-4 w-4 text-yellow-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <div class="w-32 bg-gray-200 rounded-full h-2 mx-2">
                        <div class="bg-yellow-400 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                    </div>
                    <span class="w-8 text-xs text-gray-600">{{ $count }}</span>
                </div>
            @endfor
        </div>
    </div>
    
    <!-- Lista de Avaliações -->
    <div class="space-y-4">
        @forelse($ratings as $rating)
        <div class="border-b border-gray-200 pb-4 last:border-b-0">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <div class="flex items-center mb-1">
                        <h4 class="text-sm font-medium text-gray-900">{{ $rating->user->name }}</h4>
                        <div class="ml-2 flex text-yellow-400">
                            @for($i = 1; $i <= $rating->rating; $i++)
                                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">{{ $rating->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
            
            @if($rating->comment)
            <p class="text-sm text-gray-700 leading-relaxed">{{ $rating->comment }}</p>
            @endif
        </div>
        @empty
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma avaliação ainda</h3>
            <p class="mt-1 text-sm text-gray-500">Seja o primeiro a avaliar este agente!</p>
        </div>
        @endforelse
    </div>
    
    <!-- Paginação -->
    @if($ratings->hasPages())
    <div class="mt-6">
        {{ $ratings->links() }}
    </div>
    @endif
</div>
</x-app-layout>
