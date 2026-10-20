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

namespace Beaver\Database\Model\Casts;

/**
 * Cast de datas — usa \DateTimeImmutable (nativo, sem dependências).
 *
 * Vantagens:
 *  - Imutável (mais seguro)
 *  - Zero dependências
 *  - Nativo do PHP 8
 *
 * diffForHumans() traduzido via Translator do Beaver.
 */
class BeaverDate extends \DateTimeImmutable implements CastInterface
{
    public static function apply(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new self((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * "há 3 dias", "em 2 horas", "agora".
     *
     * Usa as traduções do namespace 'date' do Translator:
     *   __('date.day')  → 'dia'
     *   __('date.days') → 'dias'
     *
     * Se o Translator não estiver disponível, cai em português.
     */
    public function diffForHumans(?\DateTimeInterface $other = null): string
    {
        $other ??= new \DateTimeImmutable();
        $diff   = $other->diff($this);

        $future  = $diff->invert === 0;
        $seconds = ($diff->days * 86400) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;

        if ($seconds < 60) {
            return $this->t('date.now', 'agora');
        }

        $units = [
            ['date.year',   'date.years',   365 * 86400],
            ['date.month',  'date.months',   30 * 86400],
            ['date.week',   'date.weeks',     7 * 86400],
            ['date.day',    'date.days',           86400],
            ['date.hour',   'date.hours',           3600],
            ['date.minute', 'date.minutes',           60],
        ];

        foreach ($units as [$singularKey, $pluralKey, $threshold]) {
            if ($seconds >= $threshold) {
                $n = (int) floor($seconds / $threshold);

                $label = $n === 1
                    ? $this->t($singularKey, $this->fallback($singularKey))
                    : $this->t($pluralKey, $this->fallback($pluralKey));

                if ($future) {
                    $prefix = $this->t('date.in', 'em');
                    return "{$prefix} {$n} {$label}";
                }

                $prefix = $this->t('date.ago', 'há');
                return "{$prefix} {$n} {$label}";
            }
        }

        return $this->t('date.now', 'agora');
    }

    // ---------- Internos ----------

    /**
     * Traduz uma chave. Se o Translator não estiver disponível,
     * devolve o fallback.
     */
    private function t(string $key, string $fallback): string
    {
        if (function_exists('__')) {
            try {
                $value = __($key);

                // Se a tradução não existir, __() devolve a chave.
                // Nesse caso, usamos o fallback.
                if ($value !== $key) {
                    return $value;
                }
            } catch (\Throwable) {
                // Sem Translator → fallback
            }
        }

        return $fallback;
    }

    /**
     * Fallback em português para cada chave.
     */
    private function fallback(string $key): string
    {
        return match ($key) {
            'date.year'   => 'ano',
            'date.years'  => 'anos',
            'date.month'  => 'mês',
            'date.months' => 'meses',
            'date.week'   => 'semana',
            'date.weeks'  => 'semanas',
            'date.day'    => 'dia',
            'date.days'   => 'dias',
            'date.hour'   => 'hora',
            'date.hours'  => 'horas',
            'date.minute' => 'minuto',
            'date.minutes'=> 'minutos',
            'date.second' => 'segundo',
            'date.seconds'=> 'segundos',
            default       => $key,
        };
    }
}
