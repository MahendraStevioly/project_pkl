<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['application_id', 'doc_type', 'file_path', 'original_name'])]
class ApplicationDocument extends Model
{
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
