<?php

namespace App\Enums;

enum LendingScope: string
{
    case Clubs = 'clubs';
    case Private = 'private';
    case Both = 'both';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Clubs => 'Nur an Vereine',
            self::Private => 'Nur an Privatpersonen',
            self::Both => 'An Vereine und Privatpersonen',
            self::None => 'Nicht verleihbar (nur intern)',
        };
    }

    public function allowsClubs(): bool
    {
        return in_array($this, [self::Clubs, self::Both], true);
    }

    public function allowsPrivate(): bool
    {
        return in_array($this, [self::Private, self::Both], true);
    }

    /** @return list<array{value:string,label:string}> */
    public static function options(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
