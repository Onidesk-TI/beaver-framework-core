<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Support;

/**
 * Class CoverageReportInjector
 *
 * Enriquece o HTML do report de cobertura com o tema visual do Frankei
 * (barras com gradiente, agrupamento por faixa, micro-info de linhas).
 *
 * @package    Beaver\Plugins\Frankei\Support
 * @author     Franco <franco@beaverphp.com>
 * @since      1.0.0
 */
class CoverageReportInjector
{
    public const MARKER = 'Frankei Coverage Redesign';

    public static function inject(string $html): string
    {
        if ($html === '' || str_contains($html, self::MARKER)) {
            return $html;
        }

        // Cache-buster: força o browser a recarregar os assets
        $v = time();

        $css      = '/plugins/frankei/css/coverage-redesign.css?v=' . $v;
        $cssHead  = '/plugins/frankei/css/coverage-header.css?v=' . $v;
        $cssSide  = '/plugins/frankei/css/coverage-sidebar.css?v=' . $v;
        $redesign = '/plugins/frankei/js/coverage-redesign.js?v=' . $v;
        $adapter  = '/plugins/frankei/js/coverage-report-adapter.js?v=' . $v;
        $inline   = '/plugins/frankei/js/coverage-inline.js?v=' . $v;
        $m        = self::MARKER;

        $head = "<!-- === $m === -->\n"
            . "<link rel=\"stylesheet\" href=\"$css\">\n"
            . "<link rel=\"stylesheet\" href=\"$cssHead\">\n"
            . "<link rel=\"stylesheet\" href=\"$cssSide\">\n"
            . "<style>\n"
            // ═══════ Reset + tema Beaver ═══════
            . ":root{--bg:#fdf8ef;--card:#fff;--text:#2E1A10;--muted:#6b5544;--border:#e8dcc8;--accent:#c25a1f;--accent-2:#e8a44f;--green:#10b981;--red:#ef4444;--yellow:#f59e0b;}\n"
            . "*,*::before,*::after{box-sizing:border-box;}\n"
            . "html,body{background:var(--bg)!important;color:var(--text)!important;margin:0!important;padding:0!important;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,sans-serif!important;font-size:13.5px!important;line-height:1.55!important;color-scheme:only light!important;}\n"
            . "a{color:var(--accent)!important;text-decoration:none!important;}\n"
            . "a:hover{color:#c2410c!important;text-decoration:underline!important;}\n"
            // ═══════ Container / Header ═══════
            . "header{background:#fff!important;border-bottom:1px solid var(--border)!important;padding:12px 22px!important;}\n"
            . ".container-fluid{padding:18px 22px!important;max-width:100%!important;}\n"
            . ".breadcrumb{background:transparent!important;padding:0!important;margin:0!important;font-size:12px!important;display:flex;flex-wrap:wrap;gap:6px;list-style:none;}\n"
            . ".breadcrumb-item{color:var(--muted)!important;}\n"
            . ".breadcrumb-item+.breadcrumb-item::before{content:'/';color:var(--muted);margin-right:6px;}\n"
            . ".breadcrumb-item a{color:var(--accent)!important;}\n"
            . ".breadcrumb-item.active{color:var(--text)!important;font-weight:600;}\n"
            // ═══════ Tabela de estatísticas (topo) ═══════
            . "table.table-bordered{background:#fff!important;border:1px solid var(--border)!important;border-radius:10px!important;overflow:hidden!important;margin:0 0 16px 0!important;width:100%!important;border-collapse:separate!important;border-spacing:0!important;}\n"
            . "table.table-bordered td,table.table-bordered th{border:0!important;border-bottom:1px solid #f0e8d8!important;padding:8px 12px!important;font-size:11.5px!important;vertical-align:middle!important;}\n"
            . "table.table-bordered tr:last-child td{border-bottom:0!important;}\n"
            . "table.table-bordered thead td{background:#faf6ee!important;color:var(--muted)!important;font-weight:700!important;text-transform:uppercase!important;letter-spacing:.04em!important;}\n"
            . "table.table-bordered td.big{font-size:12px!important;}\n"
            . "table.table-bordered td.success,table.table-bordered td.danger,table.table-bordered td.warning{background:transparent!important;}\n"
            . "table.table-bordered td.danger:not(:first-child){color:#b91c1c!important;}\n" . "table.table-bordered tbody tr[data-fk-summary=\"1\"] > td:first-child{color:#6b7280!important;}"

            // ─── Override: nome do método (1ª célula) fica cinza ───
            // (as <td class="danger"> também apanham o nome do método,  
            //  que devia ser cinza; só a % e os números têm cor)          
            . "table.table-bordered tbody tr[data-fk-summary=\"1\"] > td:first-child{color:#6b7280!important;}"
            . "table.table-bordered tbody tr[data-fk-summary=\"1\"] > td:first-child .fk-summary-dot{color:inherit!important;}"
            . "table.table-bordered tbody tr.fk-summary-row-low > td:first-child,"
            . "table.table-bordered tbody tr.fk-summary-row-medium > td:first-child,"
            . "table.table-bordered tbody tr.fk-summary-row-high > td:first-child,"
            . "table.table-bordered tbody tr.fk-summary-row-good > td:first-child{color:#6b7280!important;}"
            . "table.table-bordered td.success:not(:first-child){color:#15803d!important;}\n"
            . "table.table-bordered td.warning:not(:first-child){color:#b45309!important;}\n"
            // Progress bars do PHPUnit
            . ".progress{background:#e5ddd0!important;height:8px!important;border-radius:999px!important;overflow:hidden!important;display:flex!important;}\n"
            . ".progress-bar{background:linear-gradient(90deg,#c2410c,#f97316)!important;border-radius:999px!important;height:100%!important;}\n"
            . ".progress-bar.bg-danger{background:linear-gradient(90deg,#7f1d1d,#dc2626)!important;}\n"
            . ".progress-bar.bg-warning{background:linear-gradient(90deg,#b45309,#f59e0b)!important;}\n"
            . ".progress-bar.bg-success{background:linear-gradient(90deg,#15803d,#22c55e)!important;}\n"
            // ═══════ TABELA DO CÓDIGO-FONTE ═══════
            // Reset ao Bootstrap que parte o layout
            . "table#code{background:#fff!important;border:1px solid var(--border)!important;border-radius:10px!important;overflow:hidden!important;width:100%!important;margin:0!important;border-collapse:collapse!important;table-layout:fixed!important;}\n"
            . "table#code tbody{display:block!important;width:100%!important;}\n"
            // CRÍTICO: matar o display:flex que o Bootstrap mete nas <tr>
            . "table#code tr{display:flex!important;align-items:stretch!important;width:100%!important;border-bottom:1px solid transparent!important;}\n"
            . "table#code tr:hover{background:rgba(194,90,31,.04)!important;}\n"
            . "table#code td{border:0!important;padding:1px 0!important;font-family:ui-monospace,'JetBrains Mono',Menlo,Consolas,monospace!important;font-size:12.5px!important;line-height:1.65!important;vertical-align:top!important;background:transparent!important;}\n"
            // Coluna do número da linha
            . "table#code td.col-1,table#code td.text-end{flex:0 0 52px!important;max-width:52px!important;width:52px!important;color:#9ca3af!important;background:#faf6ee!important;text-align:right!important;padding:1px 10px 1px 4px!important;user-select:none!important;border-right:1px solid var(--border)!important;font-size:11px!important;}\n"
            . "table#code td.col-1 a{color:#9ca3af!important;}\n"
            . "table#code td.col-1 a:hover{color:var(--accent)!important;text-decoration:none!important;}\n"
            // Coluna do código
            . "table#code td.col-11,table#code td.codeLine{flex:1 1 auto!important;min-width:0!important;padding:1px 14px!important;color:var(--text)!important;white-space:pre-wrap!important;word-break:break-word!important;}\n"
            // Cores das linhas (covered / danger / warning)
            . "table#code tr.covered{background:rgba(34,197,94,.10)!important;}\n"
            . "table#code tr.covered td.col-1{background:rgba(34,197,94,.15)!important;color:#15803d!important;font-weight:700!important;}\n"
            . "table#code tr.covered td.col-1 a{color:#15803d!important;}\n"
            . "table#code tr.danger,table#code tr.notCovered{background:rgba(239,68,68,.10)!important;}\n"
            . "table#code tr.danger td.col-1,table#code tr.notCovered td.col-1{background:rgba(239,68,68,.15)!important;color:#b91c1c!important;font-weight:700!important;}\n"
            . "table#code tr.danger td.col-1 a,table#code tr.notCovered td.col-1 a{color:#b91c1c!important;}\n"
            . "table#code tr.warning{background:rgba(245,158,11,.10)!important;}\n"
            . "table#code tr.warning td.col-1{background:rgba(245,158,11,.15)!important;color:#b45309!important;}\n"
            // Syntax highlighting do PHPUnit
            . ".default{color:#2E1A10!important;}\n"
            . ".keyword{color:#c2410c!important;font-weight:600!important;}\n"
            . ".comment{color:#8a7a68!important;font-style:italic!important;}\n"
            . ".string,.string *{color:#15803d!important;}\n"
            . ".number{color:#0369a1!important;}\n"
            // ═══════ Footer ═══════
            . "footer,.footer{background:#faf6ee!important;border-top:1px solid var(--border)!important;color:var(--muted)!important;padding:10px 22px!important;font-size:11px!important;text-align:center!important;}\n"
            . ".fk-code-wrap{width:100%!important;max-width:100%!important;margin:0!important;padding:0 22px 24px 22px!important;box-sizing:border-box!important;overflow-x:auto!important;}\n"
            . ".fk-code-wrap table#code{width:100%!important;max-width:100%!important;margin:0!important;}\n"
            . "</style>\n"
            . "<!-- === /$m === -->";

        $body = "<!-- === $m (scripts) === -->\n"
            . "<script src=\"$redesign\" defer></script>\n"
            . "<script src=\"$adapter\" defer></script>\n"
            . "<script src=\"$inline\" defer></script>\n"
            . "<script src=\"/plugins/frankei/js/coverage-header.js?v=$v\" defer></script>\n"
            . "<script src=\"/plugins/frankei/js/coverage-sidebar.js?v=$v\" defer></script>\n"
            . "<!-- === /$m === -->";

        if (preg_match('#</head>#i', $html)) {
            $html = preg_replace('#</head>#i', $head . "\n</head>", $html, 1) ?? $html;
        } else {
            $html = $head . $html;
        }

        if (preg_match('#</body>#i', $html)) {
            $html = preg_replace('#</body>#i', $body . "\n</body>", $html, 1) ?? $html;
        } else {
            $html .= $body;
        }

        return $html;
    }
}
