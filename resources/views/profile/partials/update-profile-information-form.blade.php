<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Informação do Perfil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Mantenha suas informações atualiyadas.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">

        @csrf
        @method('PATCH')

        <div>
            <div class="flex flex-col items-start">
                <img id="profile-photo-preview"
                    src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=23272a&color=fff&size=128' }}"
                    class="w-24 h-24 rounded-full border mb-2 object-cover"
                    alt="Profile Photo">
                
                <!-- Input escondido -->
                <input id="profile_photo" name="profile_photo" type="file" accept="image/*" class="hidden" onchange="showFileName(this)">
                
                <!-- Botão customizado -->
                <label for="profile_photo"
                    class="inline-flex items-center justify-center px-4 py-2 bg-black border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-800 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
                    style="font-family: 'Inter', 'Figtree', Arial, sans-serif;">
                    {{ __('Foto') }}
                </label>

            </div>
            <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- CEP -->
        <div>
            <x-input-label for="cep" :value="__('CEP')" />
            <x-text-input id="cep" name="cep" type="text" class="mt-1 block w-full"
                :value="old('cep', $user->cep)" autocomplete="cep" maxlength="9" />
            <x-input-error class="mt-2" :messages="$errors->get('cep')" />
        </div>

        <!-- Endereço -->
        <div>
            <x-input-label for="address" :value="__('Endereço')" />
            <x-text-input id="address" name="address" type="text" class="mt-1 block w-full"
                :value="old('address', $user->address)" autocomplete="address" />
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <!-- Número -->
        <div>
            <x-input-label for="number" :value="__('Número')" />
            <x-text-input id="number" name="number" type="text" class="mt-1 block w-full"
                :value="old('number', $user->number)" autocomplete="number" />
            <x-input-error class="mt-2" :messages="$errors->get('number')" />
        </div>

        <!-- Cidade -->
        <div>
            <x-input-label for="city" :value="__('Cidade')" />
            <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                :value="old('city', $user->city)" autocomplete="city" />
            <x-input-error class="mt-2" :messages="$errors->get('city')" />
        </div>

        <!-- Estado -->
        <div>
            <x-input-label for="state" :value="__('Estado')" />
            <x-text-input id="state" name="state" type="text" class="mt-1 block w-full"
                :value="old('state', $user->state)" autocomplete="state" />
            <x-input-error class="mt-2" :messages="$errors->get('state')" />
        </div>


        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('profile_photo');
        const preview = document.getElementById('profile-photo-preview');

        input.addEventListener('change', function (e) {
            const [file] = input.files;
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
        const cepInput = document.getElementById('postal_code');
        const addressInput = document.getElementById('address');
        const provinceInput = document.getElementById('province');
        const cityInput = document.getElementById('city');
        const stateInput = document.getElementById('state');

        cepInput.addEventListener('blur', function() {
            let cep = cepInput.value.replace(/\D/g, '');

            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            addressInput.value = data.logradouro || '';
                            provinceInput.value = data.bairro || '';
                            cityInput.value = data.localidade || '';
                            stateInput.value = data.uf || '';
                        }
                    });
            }
        });
    });

</script>
