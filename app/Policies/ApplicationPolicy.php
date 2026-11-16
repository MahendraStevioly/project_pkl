<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['operator', 'admin', 'magang', 'karyawan'], true);
    }

    public function view(User $user, Application $application): bool
    {
        return $user->isOperator()
            || $user->isAdmin()
            || (int) $application->applicant_id === (int) $user->getKey();
    }
}
