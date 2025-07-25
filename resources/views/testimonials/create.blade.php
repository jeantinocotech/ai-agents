{{-- resources/views/testimonials/create.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Enviar Depoimento</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto mt-8 p-6 bg-white rounded-lg shadow">
        @if (session('success'))
            <div class="mb-4 p-4 bg-green-200 text-green-900 rounded">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('testimonials.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- Escolher imagem --}}
            <div>
                <x-input-label value="Escolha sua foto ou avatar" />
                <div class="flex flex-wrap gap-4 mt-3">

                    {{-- Foto de perfil --}}
                    @if($userPhoto)
                        <label>
                            <input type="radio" name="author_image" value="profile_photo"
                                {{ old('author_image', $selected_avatar) == 'profile_photo' ? 'checked' : '' }}>
                            <img src="{{ $userPhoto }}" class="w-16 h-16 rounded-full border-2 border-blue-500 mx-auto">
                            <div class="text-xs text-center mt-1">Foto do Perfil</div>
                        </label>
                    @endif

                    {{-- Avatares --}}
                    @foreach($avatars as $avatar)
                        <label>
                            <input type="radio" name="author_image" value="{{ $avatar }}"
                                {{ old('author_image', $selected_avatar) == $avatar ? 'checked' : '' }}>
                            <img src="{{ asset($avatar) }}" class="w-16 h-16 rounded-full border mx-auto">
                            <div class="text-xs text-center mt-1">Avatar</div>
                        </label>
                    @endforeach

                    {{-- Upload personalizado --}}
                    <label>
                        <input type="radio" name="author_image" value="upload" {{ old('author_image') == 'upload' ? 'checked' : '' }}>
                        <input type="file" name="author_image_upload" accept="image/*" class="mt-2 block">
                        <div class="text-xs text-center mt-1">Upload</div>
                    </label>
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('author_image')" />
                <x-input-error class="mt-2" :messages="$errors->get('author_image_upload')" />
            </div>

            {{-- Nome a exibir --}}
            <div>
                <x-input-label for="author_name" :value="'Nome a exibir'" />
                <x-text-input id="author_name" name="author_name" type="text" class="block w-full"
                    value="{{ old('author_name', Auth::user()->name) }}" required />
                <x-input-error class="mt-2" :messages="$errors->get('author_name')" />
            </div>

            {{-- Ocupação --}}
            <div>
                <x-input-label for="author_role" :value="'Ocupação (opcional)'" />
                <x-text-input id="author_role" name="author_role" type="text" class="block w-full"
                    value="{{ old('author_role') }}" />
                <x-input-error class="mt-2" :messages="$errors->get('author_role')" />
            </div>

            {{-- Depoimento --}}
            <div>
                <x-input-label for="content" :value="'Depoimento'" />
                <textarea id="content" name="content" class="block w-full mt-1" rows="4" required>{{ old('content') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('content')" />
            </div>

            {{-- Sobre qual agente --}}
            <div>
                <x-input-label for="agent_id" :value="'Sobre qual agente (opcional)'" />
                <select name="agent_id" id="agent_id" class="block w-full">
                    <option value="">Geral</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ old('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('agent_id')" />
            </div>

            <x-primary-button>Enviar depoimento</x-primary-button>
        </form>
    </div>
</x-app-layout>


