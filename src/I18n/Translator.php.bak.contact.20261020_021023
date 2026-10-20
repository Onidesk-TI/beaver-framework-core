<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\I18n;

class Translator
{
    /** @var array<string, array<string, string>> traduções carregadas por locale */
    private array $loaded = [];

    /** @var string locale ativo */
    private string $locale;

    /** @var string locale de fallback */
    private string $fallback;

    /** Unique instance shared by application (used by helpers __() e _e()) */
    private static ?self $instance = null;



      // ---------- namespaces (plugin translations) ----------

    /** @var array<string, string> namespaces: ['sms' => '/path/to/lang'] */
    private static array $namespaces = [];


    public function __construct(
        private string $langPath,
        string $locale = 'pt',
        string $fallback = 'pt',
    ) {
        $this->locale   = $locale;
        $this->fallback = $fallback;
    }



    /**
     * Return the active Translator instance.
     *
     * Used internally by the global __() and _e() helpers.
     * Throws if the Translator has not been initialized during bootstrap yet.
     *
     * @throws \RuntimeException if boot() has not been called
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Translator not initialized.');
        }
        return self::$instance;
    }

    /**
     * Register the global Translator instance.
     *
     * Call once during application bootstrap, before any
     * __() or _e() call.
     *
     * Example:
     *   Translator::boot(new Translator(__DIR__ . '/../lang'));
     */
    public static function boot(self $translator): void
    {
        self::$instance = $translator;
    }

    // ---------- public API  ----------

    /** Traduz uma chave. Aceita placeholders :nome */
    public function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale ??= $this->locale;

     // 1. Tentar chave direta (compatibilidade com chaves planas tipo "errors.404")
        $line = $this->load($locale)[$key] ?? null;

     // 2. Se não encontrou, tentar resolução por dot notation
        if ($line === null) {
              $line = $this->resolveDot($this->load($locale), $key);
        }

     // 3. Fallback se não existir no locale ativo
        if ($line === null && $locale !== $this->fallback) {
             $line = $this->load($this->fallback)[$key] ?? null;

            if ($line === null) {
                $line = $this->resolveDot($this->load($this->fallback), $key);
            }
        }

        if ($line === null) {
            return $key;
        }

        return $this->replace($line, $replace);
    }

/**
 * Resolve uma chave com dots dentro de um array aninhado.
 *   resolveDot($lang, 'validation.email')  →  $lang['validation']['email']
 */
    private function resolveDot(array $data, string $key): ?string
    {
        if (!str_contains($key, '.')) {
            return null;
        }

        $segments = explode('.', $key);
        $value    = $data;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return is_string($value) ? $value : null;
    }

    /** Igual ao get, mas faz echo (útil em views) */
    public function e(string $key, array $replace = [], ?string $locale = null): void
    {
        echo $this->get($key, $replace, $locale);
    }

    /** Pluralização simples: "item|itens" escolhe com base em $count */
    public function choice(string $key, int $count, array $replace = [], ?string $locale = null): string
    {
        $line = $this->get($key, $replace, $locale);
        $parts = explode('|', $line);

        if (count($parts) === 1) {
            return $this->replace($parts[0], array_merge($replace, ['count' => $count]));
        }

        $form = $count === 1 ? $parts[0] : $parts[1];

        return $this->replace($form, array_merge($replace, ['count' => $count]));
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public function getFallback(): string
    {
        return $this->fallback;
    }

    // ---------- internos ----------

        /**
     * Load and cache all translation lines for a locale.
     *
     * Sources, merged in this order (later overrides earlier on conflict):
     *   1. The base lang folder passed to the constructor (project/framework).
     *   2. Every registered namespace (plugin translations), keyed as
     *      "<namespace>::<key>".
     */
    private function load(string $locale): array
    {
        if (isset($this->loaded[$locale])) {
            return $this->loaded[$locale];
        }

        // Base translations: <langPath>/<locale>.php
        $data = $this->loadFile(rtrim($this->langPath, '/') . '/' . $locale . '.php');

        // Merge each registered namespace (e.g. 'sms::', 'shop::')
        foreach (self::$namespaces as $ns => $path) {
            foreach ($this->loadFile($path . '/' . $locale . '.php') as $key => $value) {
                $data[$ns . '::' . $key] = $value;
            }
        }

        return $this->loaded[$locale] = $data;
    }

    /**
     * Load a single PHP lang file and return its array.
     *
     * Returns an empty array if the file is missing or does not
     * return an array (so callers can merge blindly).
     */
    private function loadFile(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $data = require $file;

        return is_array($data) ? $data : [];
    }

    /** Substitui os placeholders :nome pelos valores em $replace. */
    private function replace(string $line, array $replace): string
    {
        foreach ($replace as $k => $v) {
            $line = str_replace(':' . $k, (string) $v, $line);
        }
        return $line;
    }

      /**
     * Register a translation namespace.
     *
     * Keys from the namespace's lang files become accessible via
     * "<namespace>::<key>", e.g. __('sms::welcome').
     *
     * Typically called from PluginBase::loadTranslations().
     */
    public static function addNamespace(string $name, string $path): void
    {
        self::$namespaces[$name] = rtrim($path, '/');

    // Invalida a cache do locale na instância ativa,
    // senão traduções adicionadas tarde nunca aparecem.
        if (self::$instance !== null) {
            self::$instance->loaded = [];
        }
    }

    /** Return all registered namespaces (name => path). */
    public static function namespaces(): array
    {
        return self::$namespaces;
    }
}
