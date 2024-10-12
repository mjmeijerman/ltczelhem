<?php

declare(strict_types=1);

namespace LtcZelhem\App\Domain\Security;

use Assert\Assertion;

/**
 * @implements \IteratorAggregate<int, UserRole>
 */
final readonly class Roles implements \IteratorAggregate
{
    /**
     * @param UserRole[] $roles
     */
    public function __construct(private array $roles)
    {
        if (count($roles) === 0) {
            throw new \InvalidArgumentException('At least one role must be provided');
        }
        Assertion::allIsInstanceOf($roles, UserRole::class);
    }

    public function contains(UserRole $roleToCheck): bool
    {
        foreach ($this->roles as $role) {
            if ($role->equals($roleToCheck)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \ArrayIterator<int, UserRole>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->roles);
    }

    /**
     * @return string[]
     */
    public function toStringArray(): array
    {
        $roles = [];
        foreach ($this->roles as $role) {
            $roles[] = $role->value;
        }

        return $roles;
    }

    public function count(): int
    {
        return count($this->roles);
    }

    public function first(): UserRole
    {
        return $this->roles[0];
    }
}
