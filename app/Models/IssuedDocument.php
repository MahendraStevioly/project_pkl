<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'placement_id',
    'template_id',
    'issued_by',
    'type',
    'doc_number',
    'status',
    'file_path',
    'requested_at',
    'issued_at',
])]
class IssuedDocument extends Model
{
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'issued_at' => 'datetime',
        ];
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(InternPlacement::class, 'placement_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
