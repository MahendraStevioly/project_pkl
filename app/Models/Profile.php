<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

#[Fillable([
    'user_id',
    'full_name',
    'nik_encrypted',
    'phone_encrypted',
    'birth_place',
    'birth_date',
    'address',
    'last_education',
    'photo_path',
])]
#[Hidden(['nik_encrypted', 'nik_hash', 'phone_encrypted'])]
class Profile extends Model
{
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    protected function nikEncrypted(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null ? null : Crypt::decryptString($value),
            set: fn (?string $value): array => $value === null
                ? ['nik_encrypted' => null, 'nik_hash' => null]
                : [
                    'nik_encrypted' => Crypt::encryptString($value),
                    'nik_hash' => hash_hmac('sha256', $value, config('app.key')),
                ],
        );
    }

    protected function phoneEncrypted(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null ? null : Crypt::decryptString($value),
            set: fn (?string $value): ?string => $value === null ? null : Crypt::encryptString($value),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
