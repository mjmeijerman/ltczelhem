<?php

namespace LtcZelhem\App\Domain\Security;

enum UserRole: string
{
    case ROLE_ACTIVITY_MANAGER = 'ROLE_ACTIVITY_MANAGER';
    case ROLE_ADMIN = 'ROLE_ADMIN';

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
            self::ROLE_ACTIVITY_MANAGER->value,
            self::ROLE_ADMIN->value,
        ];
    }
}
