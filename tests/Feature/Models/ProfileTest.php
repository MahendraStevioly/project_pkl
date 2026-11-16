<?php

namespace Tests\Feature\Models;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProfileTest extends PostgresFeatureTestCase
{
    public function test_profile_encrypts_and_decrypts_sensitive_values_and_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $nik = '3578001234567890';
        $phone = '081234567890';

        $profile = Profile::create([
            'user_id' => $user->id,
            'full_name' => 'Test Applicant',
            'nik_encrypted' => $nik,
            'phone_encrypted' => $phone,
        ]);

        $storedProfile = DB::table('profiles')->where('id', $profile->id)->first();

        $this->assertNotSame($nik, $storedProfile->nik_encrypted);
        $this->assertNotSame($phone, $storedProfile->phone_encrypted);
        $this->assertSame(hash_hmac('sha256', $nik, config('app.key')), $storedProfile->nik_hash);

        $profile->refresh();

        $this->assertSame($nik, $profile->nik_encrypted);
        $this->assertSame($phone, $profile->phone_encrypted);
        $this->assertTrue($profile->user->is($user));
        $this->assertArrayNotHasKey('nik_encrypted', $profile->toArray());
        $this->assertArrayNotHasKey('phone_encrypted', $profile->toArray());
    }
}
