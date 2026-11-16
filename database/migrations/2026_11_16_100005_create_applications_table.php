<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_opening_id')->constrained('job_openings')->restrictOnDelete();
            $table->enum('status', ['draft', 'submitted', 'in_review', 'interview', 'accepted', 'rejected'])
                ->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['applicant_id', 'job_opening_id']);   // 1 user hanya 1x melamar per lowongan
            $table->index(['status', 'submitted_at']);            // dipakai dashboard & daftar review
            $table->index('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
