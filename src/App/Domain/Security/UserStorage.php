<?php

declare(strict_types=1);

namespace LtcZelhem\App\Domain\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class UserStorage implements UserStorageInterface
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public function getUser(): ?UserCredentials
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }
        $user = $token->getUser();
        if (!$user instanceof UserCredentials) {
            return null;
        }

        return $user;
    }

    public function getUserWhenLoggedIn(): UserCredentials
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            throw new \LogicException('Expected a logged in user, but none was found');
        }
        $user = $token->getUser();
        if (!$user instanceof UserCredentials) {
            throw new \LogicException('Expected a logged in user, but none was found');
        }

        return $user;
    }
}
