<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel ringkasan untuk dashboard SIE. Diisi oleh proses ETL (artisan etl:run)
 * dari tabel transaksi (applications, job_openings), dicatat di etl_runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date');
            $table->foreignId('job_opening_id')->constrained('job_openings')->cascadeOnDelete();
            $table->enum('type', ['magang', 'kerja']);
            $table->unsignedInteger('submitted')->default(0);
            $table->unsignedInteger('accepted')->default(0);
            $table->unsignedInteger('rejected')->default(0);
            $table->decimal('avg_review_days', 6, 2)->nullable();
            $table->timestamps();

            $table->unique(['stat_date', 'job_opening_id']);
            $table->index('stat_date');
        });

        Schema::create('etl_runs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('rows_loaded')->default(0);
            $table->enum('status', ['running', 'success', 'failed'])->default('running');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etl_runs');
        Schema::dropIfExists('dashboard_daily_stats');
    }
};
