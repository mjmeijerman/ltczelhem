<?php

namespace LtcZelhem\App\Domain\Security;

enum UserRole: string
{
    case ROLE_TRAINER = 'ROLE_TRAINER';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public function equals(UserRole $other): bool
    {
        return $other->value === $this->value;
    }

    /**
     * @return array<string>
     */
    public static function allAsStringArray(): array
    {
        return [
            self::ROLE_TRAINER->value,
            self::ROLE_ADMIN->value,
            self::ROLE_SUPER_ADMIN->value,
        ];
    }
}
