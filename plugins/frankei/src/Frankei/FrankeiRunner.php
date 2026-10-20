<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei;

class FrankeiRunner
{
    public function __construct(private ScannerRegistry $registry) {}

    public function run(ScanContext $ctx, array $scannerIds = []): array
    {
        $findings = [];
        $errors   = [];

        $toRun = $scannerIds === []
            ? $this->registry->all()
            : array_filter(array_map(fn($id) => $this->registry->byId($id), $scannerIds));

        $t0 = microtime(true);

        foreach ($toRun as $id => $scanner) {
            try {
                foreach ($scanner->run($ctx) as $f) {
                    $findings[] = $f->toArray();
                }
            } catch (\Throwable $e) {
                $errors[] = ['scanner' => $id, 'message' => $e->getMessage()];
            }
        }

        return [
            'findings' => $findings,
            'stats'    => $this->computeStats($findings, microtime(true) - $t0),
            'errors'   => $errors,
        ];
    }

    private function computeStats(array $findings, float $elapsed): array
    {
        $bySeverity = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'info' => 0];
        $byCategory = [];

        foreach ($findings as $f) {
            $sev = $f['severity'] ?? 'info';
            $cat = $f['category'] ?? 'other';
            if (isset($bySeverity[$sev])) $bySeverity[$sev]++;
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + 1;
        }

        return [
            'total'       => count($findings),
            'by_severity' => $bySeverity,
            'by_category' => $byCategory,
            'elapsed_ms'  => (int) round($elapsed * 1000),
        ];
    }
}
