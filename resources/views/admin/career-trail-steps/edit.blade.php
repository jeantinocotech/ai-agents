<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar passo: {{ $step->title }} <span class="text-gray-500 font-normal text-base">({{ $step->slug }})</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('admin.career-trail-steps.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Voltar à lista</a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.career-trail-steps.update', $step) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Título do passo</label>
                        <input type="text" name="title" id="title" required maxlength="255"
                               value="{{ old('title', $step->title) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="short_description" class="block text-sm font-medium text-gray-700">Descrição curta</label>
                        <p class="text-xs text-gray-500 mt-0.5">Resumo do passo (lista da trilha, cartões).</p>
                        <textarea name="short_description" id="short_description" rows="4"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('short_description', $step->short_description) }}</textarea>
                        @error('short_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="graca_guidance" class="block text-sm font-medium text-gray-700">Orientação da Graça</label>
                        <p class="text-xs text-gray-500 mt-0.5">Texto que a mentora mostra neste passo (página da trilha / ATS).</p>
                        <textarea name="graca_guidance" id="graca_guidance" rows="8"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('graca_guidance', $step->graca_guidance) }}</textarea>
                        @error('graca_guidance')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                               @checked(old('is_active', $step->is_active ? '1' : '0') === '1')>
                        <label for="is_active" class="text-sm font-medium text-gray-700">Passo activo na trilha</label>
                    </div>
                    @error('is_active')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Guardar
                        </button>
                        <a href="{{ route('admin.career-trail-steps.index') }}" class="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
