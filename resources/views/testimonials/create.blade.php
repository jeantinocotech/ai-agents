{{-- resources/views/testimonials/create.blade.php --}}

<x-app-layout>
    <div class="max-w-2xl mx-auto py-12 px-4">
        <h2 class="text-2xl font-bold mb-8 text-gray-900">Envie seu depoimento</h2>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-200 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('testimonials.store') }}" enctype="multipart/form-data" class="space-y-6 bg-white p-8 rounded-xl shadow">
            @csrf

            <!-- Nome -->
            <div>
                <label class="block font-semibold mb-2">Seu nome</label>
                <input type="text" name="author_name" class="w-full border-gray-300 rounded" value="{{ old('author_name', Auth::user()->name ?? '') }}" required>
            </div>

            <!-- Papel -->
            <div>
                <label class="block font-semibold mb-2">Profissão/Cargo (opcional)</label>
                <input type="text" name="author_role" class="w-full border-gray-300 rounded" value="{{ old('author_role') }}">
            </div>

            <!-- Depoimento -->
            <div>
                <label class="block font-semibold mb-2">Depoimento</label>
                <textarea name="content" rows="5" class="w-full border-gray-300 rounded" required>{{ old('content') }}</textarea>
            </div>

            <!-- Selecionar agente -->
            <div>
                <label class="block font-semibold mb-2">Sobre qual agente? <span class="text-gray-400 text-xs">(opcional)</span></label>
                <select name="agent_id" class="w-full border-gray-300 rounded">
                    <option value="">Geral / Plataforma</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Escolha imagem/avatar -->
            <div>
                <label class="block font-semibold mb-2">Foto de perfil ou Avatar</label>
                @php
                    $current_avatar = old('author_image', $selected_avatar ?? Auth::user()?->profile_photo_url);
                @endphp

                <div class="flex items-center space-x-6">
                    {{-- Imagem do usuário --}}
                    @if(Auth::user()?->profile_photo_url)
                        <label class="flex items-center space-x-2">
                            <input type="radio" name="author_image" value="{{ Auth::user()->profile_photo_url }}"
                                {{ $current_avatar == Auth::user()->profile_photo_url ? 'checked' : '' }}>
                            <img src="{{ Auth::user()->profile_photo_url }}" class="h-10 w-10 rounded-full object-cover border">
                            <span class="text-gray-600 text-sm">Usar minha foto</span>
                        </label>
                    @endif

                    {{-- Avatares padrão --}}
                    <div class="mb-4">
                        <label class="block font-medium mb-2">Escolha um avatar</label>
                        <div class="flex flex-wrap gap-4">
                            @foreach($avatars as $avatar)
                                <label class="flex flex-col items-center cursor-pointer">
                                    <input type="radio" name="author_image" value="{{ $avatar }}" class="hidden"
                                        {{ old('author_image', $selectedAvatar ?? '') === $avatar ? 'checked' : '' }}>
                                    <img src="{{ asset($avatar) }}" alt="Avatar" class="w-16 h-16 rounded-full border-2
                                        border-transparent hover:border-blue-400
                                        {{ old('author_image', $selectedAvatar ?? '') === $avatar ? 'border-blue-600' : '' }}">
                                </label>
                            @endforeach
                        </div>
                        <p class="text-sm text-gray-500 mt-2">
                            Ou <a href="{{ route('profile.edit') }}" class="text-blue-500 underline">adicione uma foto no perfil</a> para usar como avatar.
                        </p>
                    </div>
                    
                </div>
                <small class="text-gray-400">Sua foto nunca será publicada sem sua permissão. Se preferir, escolha um avatar.</small>
            </div>

            <!-- Botão enviar -->
            <div>
                <button type="submit" class="bg-black text-white px-6 py-2 rounded hover:bg-gray-800 transition">
                    Enviar depoimento
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

