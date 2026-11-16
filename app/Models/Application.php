<?php

namespace App\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

#[Fillable([
    'applicant_id',
    'job_opening_id',
    'status',
    'reviewed_by',
    'admin_note',
    'submitted_at',
    'reviewed_at',
])]
class Application extends Model
{
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class);
    }

    public function internPlacement(): HasOne
    {
        return $this->hasOne(InternPlacement::class);
    }

    public function accept(User $admin, ?string $note = null): void
    {
        if (! $admin->isAdmin() && ! $admin->isOperator()) {
            throw new AuthorizationException('Only admins and operators can accept applications.');
        }

        try {
            DB::select('select fn_accept_application(?, ?, ?)', [
                $this->getKey(),
                $admin->getKey(),
                $note,
            ]);
        } catch (QueryException $exception) {
            throw new RuntimeException('Unable to accept the application.', previous: $exception);
        }
    }
}
