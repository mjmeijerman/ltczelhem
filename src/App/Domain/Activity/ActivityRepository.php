<?php
declare(strict_types=1);

namespace LtcZelhem\App\Domain\Activity;

interface ActivityRepository
{
    public function create(Activity $activity): void;
    public function update(Activity $activity): void;
}