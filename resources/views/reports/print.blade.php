<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - {{ $company->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #0f172a; margin: 0; padding: 24px; font-size: 11px; line-height: 1.4; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .header { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; border-bottom: 3px solid #1e293b; padding-bottom: 10px; margin-bottom: 14px; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        .company { font-size: 12px; font-weight: bold; color: #334155; }
        .meta { text-align: right; color: #475569; font-size: 10.5px; }
        .meta div + div { margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        .data { margin-bottom: 14px; }
        .data th { background: #1e293b; color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; padding: 7px 8px; text-align: left; border: 1px solid #1e293b; }
        .data td { padding: 6px 8px; border: 1px solid #e2e8f0; vertical-align: top; word-break: break-word; }
        .data tbody tr:nth-child(even) td { background: #f8fafc; }
        .data .empty { text-align: center; color: #64748b; padding: 18px; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        .totals { width: auto; min-width: 320px; margin-left: auto; break-inside: avoid; page-break-inside: avoid; }
        .totals td { padding: 5px 10px; border-bottom: 1px solid #e2e8f0; }
        .totals td:last-child { text-align: right; font-weight: bold; white-space: nowrap; }
        .totals tr:last-child td { border-top: 2px solid #1e293b; border-bottom: 0; font-size: 12px; }
        .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid #cbd5e1; color: #64748b; font-size: 9.5px; display: flex; justify-content: space-between; }
        .actions { margin-bottom: 16px; }
        .actions button { padding: 8px 14px; border: 0; border-radius: 8px; background: #1e293b; color: #fff; font-size: 13px; cursor: pointer; }
        @media print { .actions { display: none; } body { padding: 0; } }
        @page { size: A4 landscape; margin: 12mm; }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">Imprimir / salvar como PDF</button></div>
    <div class="header">
        <div>
            <h1>{{ $title }}</h1>
            <div class="company">{{ $company->name }}</div>
        </div>
        <div class="meta">
            @if (! empty($filters['start']) || ! empty($filters['end']))
                <div><strong>Período:</strong>
                    {{ ! empty($filters['start']) ? \Illuminate\Support\Carbon::parse($filters['start'])->format('d/m/Y') : 'início' }}
                    a {{ ! empty($filters['end']) ? \Illuminate\Support\Carbon::parse($filters['end'])->format('d/m/Y') : 'hoje' }}
                </div>
            @endif
            <div><strong>Gerado em:</strong> {{ now()->format('d/m/Y H:i') }}</div>
            <div><strong>Registros:</strong> {{ count($rows) }}</div>
        </div>
    </div>

    <table class="data">
        <thead>
            <tr>@foreach ($columns as $column)<th>{{ $column }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td class="empty" colspan="{{ count($columns) }}">Nenhum registro encontrado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        @foreach ($totals as $total)
            <tr><td>{{ $total['label'] }}</td><td>{{ $total['value'] }}</td></tr>
        @endforeach
    </table>

    <div class="footer">
        <span>FinControl · {{ $company->name }}</span>
        <span>{{ $title }}</span>
    </div>
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
