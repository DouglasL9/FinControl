<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900">
            Informações pessoais
        </h2>

        <p class="mt-1 text-sm leading-6 text-slate-500">
            Atualize seu nome e endereço de e-mail.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nome" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-slate-700">
                        Seu endereço de e-mail ainda não foi verificado.

                        <button form="send-verification" class="rounded-md font-medium text-brand-primary underline decoration-brand-primary underline-offset-4 hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                            Enviar um novo e-mail de verificação
                        </button>
                    </p>

                </div>
            @endif
        </div>

        <div>
            <x-primary-button>Salvar alterações</x-primary-button>
        </div>
    </form>
</section>
