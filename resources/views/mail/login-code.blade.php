<x-mail::message :brand-name="$brandName" :brand-logo-url="$brandLogoUrl">
# {{ $greeting }}

Use o código abaixo para concluir o seu acesso:

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 24px 0;">
<tr>
<td align="center" style="background-color: #eef2ff; border: 2px dashed #818cf8; border-radius: 12px; padding: 22px 12px;">
<span style="font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 38px; font-weight: 700; letter-spacing: 12px; color: #4338ca; padding-left: 12px;">{{ $code }}</span>
</td>
</tr>
</table>

Este código expira em 10 minutos. Se você não tentou entrar, pode ignorar este e-mail com segurança.

Atenciosamente,<br>
equipe {{ $brandName }}
</x-mail::message>
