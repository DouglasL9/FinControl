<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DocumentationController extends Controller
{
    public function show(Request $request): View
    {
        $company = $request->user()->currentCompany();

        abort_if($company === null, 404);
        abort_unless(
            $request->user()->isSystemAdmin()
            || $company->hasDeveloperProfile($company->membershipFor($request->user())),
            403,
        );

        $documentPath = base_path('docs/PROJETO.md');

        abort_unless(is_file($documentPath), 404);

        $html = Str::markdown(file_get_contents($documentPath), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        ['html' => $content, 'toc' => $tableOfContents] = $this->prepareHtml($html);

        return view('documentation.show', [
            'content' => $content,
            'tableOfContents' => $tableOfContents,
            'updatedAt' => Carbon::createFromTimestamp(filemtime($documentPath)),
        ]);
    }

    /**
     * Adiciona âncoras aos títulos, envolve tabelas para rolagem horizontal e monta o índice.
     *
     * @return array{html: string, toc: list<array{level: int, id: string, title: string}>}
     */
    private function prepareHtml(string $html): array
    {
        $toc = [];
        $usedIds = [];

        $html = preg_replace_callback(
            '/<h([123])>(.*?)<\/h\1>/s',
            function (array $matches) use (&$toc, &$usedIds): string {
                $level = (int) $matches[1];
                $title = trim(html_entity_decode(strip_tags($matches[2])));
                $baseId = Str::slug($title) ?: 'secao';
                $id = $baseId;
                $suffix = 2;

                while (isset($usedIds[$id])) {
                    $id = $baseId.'-'.$suffix++;
                }

                $usedIds[$id] = true;

                if ($level > 1) {
                    $toc[] = ['level' => $level, 'id' => $id, 'title' => $title];
                }

                return "<h{$level} id=\"{$id}\">{$matches[2]}</h{$level}>";
            },
            $html,
        ) ?? $html;

        $html = str_replace(
            ['<table>', '</table>'],
            ['<div class="doc-table"><table>', '</table></div>'],
            $html,
        );

        return ['html' => $html, 'toc' => $toc];
    }
}
