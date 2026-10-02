<?php

namespace App\Enums;

enum ReturnCondition: string
{
    case Ok = 'ok';
    case Worn = 'worn';
    case Damaged = 'damaged';
    case Incomplete = 'incomplete';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'Einwandfrei',
            self::Worn => 'Gebrauchsspuren',
            self::Damaged => 'Beschädigt',
            self::Incomplete => 'Unvollständig',
        };
    }

    /** Fälle, auf die der Verein achten sollte. */
    public function needsAttention(): bool
    {
        return in_array($this, [self::Damaged, self::Incomplete], true);
    }

    /** @return list<array{value:string,label:string}> */
    public static function options(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
