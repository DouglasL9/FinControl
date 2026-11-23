<x-mail::message :brand-name="$brandName" :brand-logo-url="$brandLogoUrl">
# Você foi convidado! 🎉

**{{ $inviterName }}** convidou você para acompanhar as contas a pagar de **{{ $companyName }}**.

<x-mail::button :url="$acceptUrl">
Aceitar convite
</x-mail::button>

O convite expira em 7 dias e só pode ser aceito pelo endereço convidado.

Até já,<br>
equipe {{ $brandName }}

<x-mail::subcopy>
Se o botão não funcionar, copie e cole este link no navegador: [{{ $acceptUrl }}]({{ $acceptUrl }})
</x-mail::subcopy>
</x-mail::message>
