<?php

namespace Thetheago\SeederVersioning\Command\Concerns;

use Illuminate\Support\Str;
use Thetheago\SeederVersioning\Services\SeederRiskAnalyzer;
use Thetheago\SeederVersioning\Services\SeederVersioningService;

/**
 * The seeder table shared by seed:status and seed:migrate (before running and with --pretend).
 */
trait RendersSeederPreview
{
    /**
     * @param array<int, array<string, mixed>> $statuses As returned by SeederVersioningService::seederStatuses()
     */
    protected function renderSeederTable(array $statuses): void
    {
        $this->table(['Seeder', 'Status', 'Hash', 'Risks'], array_map(fn (array $status) => [
            $status['seeder'],
            $status['status'],
            $this->shortHash($status['hash']),
            $this->formatRisks($status['risks']),
        ], $statuses));
    }

    /**
     * @param array<int, array<string, mixed>> $statuses
     * @return array<int, array<string, mixed>>
     */
    protected function pendingSeeders(array $statuses): array
    {
        return array_values(array_filter(
            $statuses,
            fn (array $status) => $status['status'] !== SeederVersioningService::STATUS_UP_TO_DATE
        ));
    }

    /**
     * "2 seeders" plus ", 1 high risk" when there is any.
     *
     * @param array<int, array<string, mixed>> $pending
     */
    protected function pendingSummary(array $pending): string
    {
        $count = count($pending);
        $summary = $count . ' ' . Str::plural('seeder', $count);

        $high = 0;
        foreach ($pending as $status) {
            $high += count(array_filter($status['risks'], fn (array $risk) => $risk['level'] === SeederRiskAnalyzer::HIGH));
        }

        return $high ? "{$summary}, {$high} high " . Str::plural('risk', $high) : $summary;
    }

    /**
     * @param array<int, array{level: string, message: string}> $risks
     */
    protected function formatRisks(array $risks): string
    {
        if (! $risks) {
            return '-';
        }

        return implode("\n", array_map(function (array $risk) {
            $color = $risk['level'] === SeederRiskAnalyzer::HIGH ? 'red' : 'yellow';

            return "<fg={$color}>[{$risk['level']}]</> {$risk['message']}";
        }, $risks));
    }

    protected function shortHash(string $hash): string
    {
        return substr($hash, 0, 6);
    }
}
