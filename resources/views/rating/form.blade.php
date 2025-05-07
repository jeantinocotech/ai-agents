<x-app-layout>
    <div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Avalie sua experiência com {{ $chatSession->agent->name }}</h2>
                
                <form method="POST" action="{{ $existingRating ? route('ratings.update', $existingRating->id) : route('ratings.store') }}">
                    @csrf
                    @if($existingRating)
                        @method('PUT')
                    @endif
                    
                    <input type="hidden" name="agent_id" value="{{ $chatSession->agent_id }}">
                    <input type="hidden" name="chat_session_id" value="{{ $chatSession->id }}">
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-3">Sua Avaliação</label>
                        <div class="rating flex items-center space-x-1" id="rating-stars">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" class="star-button p-1 focus:outline-none" data-value="{{ $i }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 {{ ($existingRating && $existingRating->rating >= $i) ? 'text-yellow-500' : 'text-gray-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </button>
                            @endfor
                            <input type="hidden" name="rating" id="rating-value" value="{{ $existingRating ? $existingRating->rating : 0 }}" required>
                        </div>
                        @error('rating')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="mb-6">
                        <label for="comment" class="block text-gray-700 text-sm font-bold mb-2">Deixe um comentário (opcional)</label>
                        <textarea id="comment" name="comment" rows="4" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md" placeholder="Conte-nos sobre sua experiência...">{{ $existingRating ? $existingRating->comment : '' }}</textarea>
                        @error('comment')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <a href="{{ route('chat.history') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded">
                            Cancelar
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            {{ $existingRating ? 'Atualizar Avaliação' : 'Enviar Avaliação' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star-button');
            const ratingInput = document.getElementById('rating-value');
            
            stars.forEach(star => {
                star.addEventListener('click', () => {
                    const value = parseInt(star.dataset.value);
                    ratingInput.value = value;
                    
                    // Atualizar visualização
                    stars.forEach((s, index) => {
                        const starSvg = s.querySelector('svg');
                        if (index < value) {
                            starSvg.classList.remove('text-gray-300');
                            starSvg.classList.add('text-yellow-500');
                        } else {
                            starSvg.classList.remove('text-yellow-500');
                            starSvg.classList.add('text-gray-300');
                        }
                    });
                });
                
                // Efeito hover
                star.addEventListener('mouseover', () => {
                    const value = parseInt(star.dataset.value);
                    
                    stars.forEach((s, index) => {
                        const starSvg = s.querySelector('svg');
                        if (index < value) {
                            starSvg.classList.add('text-yellow-400');
                        }
                    });
                });
                
                star.addEventListener('mouseout', () => {
                    const currentRating = parseInt(ratingInput.value);
                    
                    stars.forEach((s, index) => {
                        const starSvg = s.querySelector('svg');
                        starSvg.classList.remove('text-yellow-400');
                        
                        if (index < currentRating) {
                            starSvg.classList.add('text-yellow-500');
                        } else {
                            starSvg.classList.add('text-gray-300');
                        }
                    });
                });
            });
        });
    </script>
    @endpush
</x-app-layout>