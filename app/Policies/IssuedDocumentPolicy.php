<?php

namespace App\Policies;

use App\Models\IssuedDocument;
use App\Models\User;

class IssuedDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOperator() || $user->isAdmin() || $user->isMagang();
    }

    public function view(User $user, IssuedDocument $issuedDocument): bool
    {
        if ($user->isOperator() || $user->isAdmin()) {
            return true;
        }

        return $user->isMagang()
            && (int) $issuedDocument->placement?->application?->applicant_id === (int) $user->getKey();
    }
}
