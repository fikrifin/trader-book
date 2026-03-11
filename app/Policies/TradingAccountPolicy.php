<?php

namespace App\Policies;

use App\Models\TradingAccount;
use App\Models\User;

class TradingAccountPolicy
{
    public function update(User $user, TradingAccount $account): bool
    {
        return $account->user_id === $user->id;
    }

    public function delete(User $user, TradingAccount $account): bool
    {
        return $account->user_id === $user->id;
    }
}