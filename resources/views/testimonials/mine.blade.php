<x-app-layout>
    <div class="max-w-3xl mx-auto py-10">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Meus Depoimentos</h2>
            <a href="{{ route('testimonials.create') }}" class="inline-block px-4 py-2 bg-black text-white rounded hover:bg-gray-800 transition">
                Novo Depoimento
            </a>
        </div>

        @if($testimonials->isEmpty())
            <div class="bg-white shadow rounded-lg p-8 text-center">
                <p class="text-gray-600 mb-6">Você ainda não enviou depoimentos.</p>
                <a href="{{ route('testimonials.create') }}" class="inline-block px-6 py-2 bg-black text-white rounded hover:bg-gray-800 transition">
                    Escrever meu primeiro depoimento
                </a>
            </div>
        @else
            <div class="space-y-6">
                @foreach($testimonials as $testimonial)
                    <div class="bg-white shadow rounded-lg p-6 flex space-x-4 items-center">
                        <img src="{{ $testimonial->author_image }}" class="w-14 h-14 rounded-full object-cover border" alt="Avatar">
                        <div>
                            <div class="font-bold">{{ $testimonial->author_name }}</div>
                            <div class="text-xs text-gray-400">{{ $testimonial->author_role }}</div>
                            <div class="mt-2 text-gray-800">{{ $testimonial->content }}</div>
                            @if($testimonial->is_approved)
                                <span class="inline-block mt-2 px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Aprovado</span>
                            @else
                                <span class="inline-block mt-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded">Aguardando aprovação</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-10 text-center">
                <a href="{{ route('testimonials.create') }}" class="inline-block px-6 py-2 bg-black text-white rounded hover:bg-gray-800 transition">
                    Escrever novo depoimento
                </a>
            </div>
        @endif
    </div>
</x-app-layout>

