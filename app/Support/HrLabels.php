<?php

namespace App\Support;

final class HrLabels
{
    public static function leaveType(string $type): string
    {
        return match ($type) {
            'annual' => 'Congé annuel',
            'sick' => 'Congé maladie',
            'unpaid' => 'Congé sans solde',
            'maternity' => 'Congé de maternité',
            'paternity' => 'Congé de paternité',
            'family' => 'Congé familial',
            default => 'Autre congé',
        };
    }

    public static function absenceType(string $type): string
    {
        return match ($type) {
            'unjustified' => 'Absence non justifiée',
            'medical' => 'Absence médicale',
            'family_emergency' => 'Urgence familiale',
            default => 'Autre absence',
        };
    }

    public static function requestStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'approved' => 'Approuvée',
            'rejected' => 'Rejetée',
            'cancelled' => 'Annulée',
            default => $status,
        };
    }

    public static function contractType(string $type): string
    {
        return match ($type) {
            'cdi' => 'CDI',
            'cdd' => 'CDD',
            'internship' => 'Stage',
            'interim' => 'Intérim',
            'apprenticeship' => 'Apprentissage',
            default => $type,
        };
    }

    public static function payStatus(string $status): string
    {
        return match ($status) {
            'open' => 'Ouverte',
            'closed' => 'Clôturée',
            'archived' => 'Archivée',
            'draft' => 'Brouillon',
            'validated' => 'Validé',
            'paid' => 'Payé',
            default => $status,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function leaveTypes(): array
    {
        return [
            'annual' => self::leaveType('annual'),
            'sick' => self::leaveType('sick'),
            'unpaid' => self::leaveType('unpaid'),
            'maternity' => self::leaveType('maternity'),
            'paternity' => self::leaveType('paternity'),
            'family' => self::leaveType('family'),
            'other' => self::leaveType('other'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function absenceTypes(): array
    {
        return [
            'unjustified' => self::absenceType('unjustified'),
            'medical' => self::absenceType('medical'),
            'family_emergency' => self::absenceType('family_emergency'),
            'other' => self::absenceType('other'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function contractTypes(): array
    {
        return [
            'cdi' => self::contractType('cdi'),
            'cdd' => self::contractType('cdd'),
            'internship' => self::contractType('internship'),
            'interim' => self::contractType('interim'),
            'apprenticeship' => self::contractType('apprenticeship'),
        ];
    }
}
