<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stat_date',
    'job_opening_id',
    'type',
    'submitted',
    'accepted',
    'rejected',
    'avg_review_days',
])]
class DashboardDailyStat extends Model
{
    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'submitted' => 'integer',
            'accepted' => 'integer',
            'rejected' => 'integer',
            'avg_review_days' => 'decimal:2',
        ];
    }

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }
}
