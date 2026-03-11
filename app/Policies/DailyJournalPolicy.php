<?php

namespace App\Policies;

use App\Models\DailyJournal;
use App\Models\User;

class DailyJournalPolicy
{
    public function view(User $user, DailyJournal $journal): bool
    {
        return $journal->user_id === $user->id;
    }

    public function update(User $user, DailyJournal $journal): bool
    {
        return $journal->user_id === $user->id;
    }
}