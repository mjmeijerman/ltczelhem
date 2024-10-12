<?php

namespace LtcZelhem\App\Domain\Utilities;

enum Locale: string
{
    case NL = 'nl';
    case EN = 'en';

    /**
     * @return array<string>
     */
    public static function allAsStringArray(): array
    {
        return [
            self::NL->value,
            self::EN->value,
        ];
    }

    public function equals(Locale $other): bool
    {
        return $other->value === $this->value;
    }
}
