<?php

declare(strict_types=1);

namespace LtcZelhem\App\Domain\Security;

interface UserStorageInterface
{
    public function getUser(): ?UserCredentials;

    public function getUserWhenLoggedIn(): UserCredentials;
}
