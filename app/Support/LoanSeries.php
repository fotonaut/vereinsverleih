<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/** Berechnet die Einzeltermine einer Wiederholungs-Ausleihe. */
class LoanSeries
{
    public const INTERVALS = ['weekly' => 'wöchentlich', 'biweekly' => 'alle 2 Wochen', 'monthly' => 'monatlich'];

    public const MAX_OCCURRENCES = 12;

    /**
     * @return list<array{start:string,end:string}>
     */
    public static function occurrences(string $start, string $end, string $interval, int $count): array
    {
        $first = CarbonImmutable::parse($start)->startOfDay();
        $durationDays = (int) $first->diffInDays(CarbonImmutable::parse($end)->startOfDay());
        $out = [];

        for ($i = 0; $i < $count; $i++) {
            $s = match ($interval) {
                'weekly' => $first->addWeeks($i),
                'biweekly' => $first->addWeeks($i * 2),
                'monthly' => $first->addMonthsNoOverflow($i),
            };
            $out[] = ['start' => $s->toDateString(), 'end' => $s->addDays($durationDays)->toDateString()];
        }

        return $out;
    }

    /** Frühester Abstand (in Tagen) zwischen zwei Terminen – Termine dürfen sich nicht überlappen. */
    public static function minGapDays(string $interval): int
    {
        return match ($interval) {
            'weekly' => 7,
            'biweekly' => 14,
            'monthly' => 28,
        };
    }
}
