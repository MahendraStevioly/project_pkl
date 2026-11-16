<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['caption', 'image_path', 'taken_at', 'sort_order'])]
class GalleryItem extends Model
{
    protected function casts(): array
    {
        return [
            'taken_at' => 'date',
            'sort_order' => 'integer',
        ];
    }
}
