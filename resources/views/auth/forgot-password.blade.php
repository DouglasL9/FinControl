<x-guest-layout>
    <x-slot name="heading">Recupere sua senha</x-slot>
    <x-slot name="description">Informe seu e-mail e enviaremos um link para criar uma nova senha.</x-slot>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-5 flex justify-end">
            <x-primary-button class="w-full sm:w-auto">
                Enviar link de recuperação
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
