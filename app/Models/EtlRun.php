<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['started_at', 'finished_at', 'rows_loaded', 'status', 'error_message'])]
class EtlRun extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'rows_loaded' => 'integer',
        ];
    }
}
