<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'name', 'html_template', 'is_active'])]
class DocumentTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function issuedDocuments(): HasMany
    {
        return $this->hasMany(IssuedDocument::class, 'template_id');
    }
}
