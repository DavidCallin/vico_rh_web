<?php

namespace App\Payroll;

/**
 * ITS mensuel ordinaire, impôt sur les traitements et salaires du Bénin.
 *
 * Source : Code général des impôts 2025, Ministère de l'Économie et des
 * Finances (finances.bj). L'article 122 retient comme salaire mensuel
 * imposable les montants bruts perçus, sans abattement pour frais
 * professionnels ni réduction pour charges de famille. L'article 125
 * applique le barème progressif suivant, confirmé par le Livre fiscal
 * FACE Africa mis à jour avec la loi de finances 2025 et par l'exemple
 * chiffré de la Chambre de commerce (500 000 F de base = 71 500 F d'ITS) :
 *   0 %   jusqu'à 60 000 F CFA
 *   10 %  de 60 001 à 150 000
 *   15 %  de 150 001 à 250 000
 *   19 %  de 250 001 à 500 000
 *   30 %  au-delà de 500 000
 * L'impôt est majoré de la redevance ORTB/SRTB : 1 000 F en mars et
 * 3 000 F en juin. Elle n'est pas due lorsque le revenu imposable
 * n'excède pas 60 000 F (La Nation, 28 janvier 2025, citant le CGI 2025).
 * Hors périmètre : atténuation des rémunérations exceptionnelles et
 * évaluation forfaitaire des avantages en nature.
 */
final class BeninItsCalculator
{
    public const FIRST_BRACKET_CEILING = 60_000;

    public const SRTB_MARCH = 1_000;

    public const SRTB_JUNE = 3_000;

    /**
     * Plafond de tranche inclusif et taux en pourcentage entier.
     *
     * @var list<array{0: int|null, 1: int}>
     */
    private const BRACKETS = [
        [60_000, 0],
        [150_000, 10],
        [250_000, 15],
        [500_000, 19],
        [null, 30],
    ];

    /**
     * @return array{taxable_base: int, scale_tax: int, srtb: int, its: int}
     */
    public function calculate(int|float|string $grossTaxable, int $month): array
    {
        $base = (int) round((float) $grossTaxable);

        if ($base < 0) {
            $base = 0;
        }

        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Le mois de paie doit être compris entre 1 et 12.');
        }

        $remaining = $base;
        $previous = 0;
        $numerator = 0;

        foreach (self::BRACKETS as [$ceiling, $ratePercent]) {
            $width = $ceiling === null ? $remaining : $ceiling - $previous;
            $span = min($remaining, $width);

            if ($span > 0) {
                $numerator += $span * $ratePercent;
                $remaining -= $span;
            }

            $previous = $ceiling ?? $previous;

            if ($remaining <= 0) {
                break;
            }
        }

        $scale = intdiv($numerator + 50, 100);
        $srtb = 0;

        if ($base > self::FIRST_BRACKET_CEILING) {
            if ($month === 3) {
                $srtb = self::SRTB_MARCH;
            } elseif ($month === 6) {
                $srtb = self::SRTB_JUNE;
            }
        }

        return [
            'taxable_base' => $base,
            'scale_tax' => $scale,
            'srtb' => $srtb,
            'its' => $scale + $srtb,
        ];
    }
}
