<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Services\ReportBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const SCREEN_ROW_LIMIT = 500;

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('reports.show', 'payables');
    }

    public function show(ReportFilterRequest $request, ReportBuilder $builder, string $report): View
    {
        $company = $request->user()->currentCompany();
        $data = $builder->build($company, $report, $request->validated());

        return view('reports.show', [
            'company' => $company,
            'report' => $report,
            'types' => ReportBuilder::TYPES,
            'filters' => $request->validated(),
            'columns' => $data['columns'],
            'rows' => array_slice($data['rows'], 0, self::SCREEN_ROW_LIMIT),
            'rowCount' => count($data['rows']),
            'rowLimit' => self::SCREEN_ROW_LIMIT,
            'totals' => $data['totals'],
            'suppliers' => $company->suppliers()->orderBy('name')->get(['id', 'name']),
            'units' => $company->units()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function print(ReportFilterRequest $request, ReportBuilder $builder, string $report): View
    {
        $company = $request->user()->currentCompany();
        $data = $builder->build($company, $report, $request->validated());

        return view('reports.print', [
            'company' => $company,
            'title' => ReportBuilder::TYPES[$report],
            'filters' => $request->validated(),
            'columns' => $data['columns'],
            'rows' => $data['rows'],
            'totals' => $data['totals'],
        ]);
    }

    public function export(ReportFilterRequest $request, ReportBuilder $builder, string $report): StreamedResponse
    {
        $company = $request->user()->currentCompany();
        $data = $builder->build($company, $report, $request->validated());
        $filename = "relatorio-{$report}-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, $data['columns'], ';');

            foreach ($data['rows'] as $row) {
                fputcsv($output, array_map(self::neutralizeFormula(...), $row), ';');
            }

            fputcsv($output, [], ';');

            foreach ($data['totals'] as $total) {
                fputcsv($output, [$total['label'], $total['value']], ';');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Evita injeção de fórmulas ao abrir o CSV no Excel.
     */
    private static function neutralizeFormula(string $value): string
    {
        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
