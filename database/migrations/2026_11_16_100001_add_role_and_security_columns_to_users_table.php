<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memperluas tabel `users` bawaan Laravel (id, name, email, password, ...)
 * dengan role, status akun, dan kolom keamanan (KSI: 3A, lockout, 2FA).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['operator', 'admin', 'magang', 'karyawan'])->default('karyawan');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->unsignedSmallInteger('failed_login_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->text('two_factor_secret')->nullable();          // simpan terenkripsi (cast 'encrypted')
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropIndex(['role']);
            $table->dropColumn([
                'role', 'status', 'failed_login_count', 'locked_until',
                'two_factor_secret', 'two_factor_confirmed_at',
            ]);
        });
    }
};
