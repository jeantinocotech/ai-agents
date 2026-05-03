@php
    /** @var \App\Models\CareerTrailGracaMessage|null $m */
    $m = $gracaMessage;
@endphp

<div>
    <label for="process_key" class="block text-sm font-medium text-gray-700">Chave do processo</label>
    <input type="text" name="process_key" id="process_key" required maxlength="64"
           value="{{ old('process_key', $m?->process_key ?? 'career_trail') }}"
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm font-mono text-sm">
    @error('process_key')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label for="career_trail_step_id" class="block text-sm font-medium text-gray-700">Passo da trilha</label>
    <select name="career_trail_step_id" id="career_trail_step_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        <option value="">Landing / global (sem passo)</option>
        @foreach ($steps as $st)
            <option value="{{ $st->id }}" @selected((string) old('career_trail_step_id', $m?->career_trail_step_id ?? '') === (string) $st->id)>
                {{ $st->sort_order }}. {{ $st->slug }} — {{ $st->title }}
            </option>
        @endforeach
    </select>
    @error('career_trail_step_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label for="slot" class="block text-sm font-medium text-gray-700">Slot (onde aparece)</label>
    <select name="slot" id="slot" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        @foreach ($slots as $value => $label)
            <option value="{{ $value }}" @selected(old('slot', $m?->slot ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('slot')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label for="sort_order" class="block text-sm font-medium text-gray-700">Ordem (0 = primeiro)</label>
    <input type="number" name="sort_order" id="sort_order" required min="0" max="32767"
           value="{{ old('sort_order', $m?->sort_order ?? 0) }}"
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
    @error('sort_order')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label for="body" class="block text-sm font-medium text-gray-700">Texto</label>
    <p class="text-xs text-gray-500 mt-0.5">Na página «Meu CV», use o marcador <code class="bg-gray-100 px-1 rounded">__MIN_CHARS__</code> para o mínimo de caracteres da trilha.</p>
    <textarea name="body" id="body" rows="12" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm font-mono text-sm">{{ old('body', $m?->body ?? '') }}</textarea>
    @error('body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
           @checked(old('is_active', ($m?->is_active ?? true) ? '1' : '0') === '1')>
    <label for="is_active" class="text-sm font-medium text-gray-700">Activo</label>
</div>
@error('is_active')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
