<?php

namespace App\Enums;

enum LoanStatus: string
{
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case PickedUp = 'picked_up';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'E-Mail unbestätigt',
            self::Pending => 'Angefragt',
            self::Approved => 'Genehmigt',
            self::Declined => 'Abgelehnt',
            self::PickedUp => 'Ausgeliehen',
            self::Returned => 'Zurückgegeben',
            self::Cancelled => 'Storniert',
        };
    }

    /** Status, die den Bestand im Zeitraum blockieren. */
    public static function blocking(): array
    {
        return [self::Approved, self::PickedUp];
    }

    public static function open(): array
    {
        return [self::Pending, self::Approved, self::PickedUp];
    }
}
