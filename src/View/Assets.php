<?php

declare(strict_types=1);

namespace Beaver\View;

/**
 * Asset injector — registra CSS/JS e injeta antes de </head>.
 *
 * Uso:
 *   Assets::css('/mobile-editor/css/terminal.css');
 *   Assets::js('/mobile-editor/js/beaver-terminal.js');
 *   return Response::html(Assets::inject($html));
 */
class Assets
{
    /** @var array<string, true> */
    private static array $css = [];

    /** @var array<string, array{url:string,defer:bool,async:bool}> */
    private static array $js = [];

    /**
     * Base path do projeto (pra calcular mtime). Defina no boot:
     *   Assets::setBasePath($basePath);
     */
    private static ?string $basePath = null;

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }

    // ─────────────────────────────────────────────
    //  CSS
    // ─────────────────────────────────────────────

    /**
     * Registra um CSS.
     *
     * @param string      $url       URL relativa ou absoluta (ex: /mobile-editor/css/terminal.css)
     * @param string|null $diskPath  Caminho no disco (opcional) — se passado, gera ?v=<mtime>
     */
    public static function css(string $url, ?string $diskPath = null): void
    {
        $url = self::withVersion($url, $diskPath);
        self::$css[$url] = true;
    }

    // ─────────────────────────────────────────────
    //  JS
    // ─────────────────────────────────────────────

    /**
     * Registra um JS.
     *
     * @param string      $url
     * @param bool        $defer
     * @param bool        $async
     * @param string|null $diskPath
     */
    public static function js(string $url, bool $defer = true, bool $async = false, ?string $diskPath = null): void
    {
        $url = self::withVersion($url, $diskPath);
        self::$js[$url] = ['url' => $url, 'defer' => $defer, 'async' => $async];
    }

    // ─────────────────────────────────────────────
    //  Render
    // ─────────────────────────────────────────────

    public static function renderCss(): string
    {
        if (empty(self::$css)) {
            return '';
        }

        $out = [];
        foreach (array_keys(self::$css) as $url) {
            $out[] = '<link rel="stylesheet" href="' . htmlspecialchars($url, ENT_QUOTES) . '">';
        }
        return implode("\n", $out);
    }

    public static function renderJs(): string
    {
        if (empty(self::$js)) {
            return '';
        }

        $out = [];
        foreach (self::$js as $item) {
            $attrs = '';
            if ($item['defer']) {
                $attrs .= ' defer';
            }
            if ($item['async']) {
                $attrs .= ' async';
            }

            $out[] = '<script src="' . htmlspecialchars($item['url'], ENT_QUOTES) . '"' . $attrs . '></script>';
        }
        return implode("\n", $out);
    }

    /**
     * Injeta os assets antes de </head>.
     * Se não houver </head>, tenta <body>, senão devolve o HTML inalterado.
     */
    public static function inject(string $html): string
    {
        $tags = trim(self::renderCss() . "\n" . self::renderJs());
        if ($tags === '') {
            return $html;
        }

        // 1) Antes de </head>
        if (stripos($html, '</head>') !== false) {
            return preg_replace(
                '#</head>#i',
                $tags . "\n</head>",
                $html,
                1
            ) ?? $html;
        }

        // 2) Fallback: antes de <body>
        if (preg_match('#<body[^>]*>#i', $html, $m)) {
            return preg_replace(
                '#(<body[^>]*>)#i',
                '$1' . "\n" . $tags,
                $html,
                1
            ) ?? $html;
        }

        // 3) Sem <head> nem <body> — não injeta (evita quebrar fragmentos)
        return $html;
    }

    // ─────────────────────────────────────────────
    //  Utilidades
    // ─────────────────────────────────────────────

    /**
     * Adiciona ?v=<mtime> se um diskPath for informado e o arquivo existir.
     * Se a URL já tiver ?v=, respeita.
     */
    private static function withVersion(string $url, ?string $diskPath): string
    {
        if (str_contains($url, '?v=')) {
            return $url;
        }
        if ($diskPath === null) {
            return $url;
        }

        $full = $diskPath;
        if ($full[0] !== '/' && self::$basePath !== null) {
            $full = self::$basePath . '/' . ltrim($full, '/');
        }

        if (is_file($full)) {
            $sep = str_contains($url, '?') ? '&' : '?';
            return $url . $sep . 'v=' . filemtime($full);
        }

        return $url;
    }

    public static function reset(): void
    {
        self::$css = [];
        self::$js  = [];
    }

    /** @return array{css:array<int,string>,js:array<string,array{url:string,defer:bool,async:bool}>} */
    public static function all(): array
    {
        return [
            'css' => array_keys(self::$css),
            'js'  => self::$js,
        ];
    }
}
