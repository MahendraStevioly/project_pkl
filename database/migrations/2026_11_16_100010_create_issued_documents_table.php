<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issued_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained('intern_placements')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('document_templates')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete(); // null selama masih "requested"
            $table->enum('type', ['loa', 'sertifikat', 'form_penerimaan']);
            $table->string('doc_number')->nullable()->unique();   // contoh: 001/LoA/EWF-SBY/XI/2026
            $table->enum('status', ['requested', 'issued', 'revoked'])->default('requested');
            $table->string('file_path')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->index(['placement_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_documents');
    }
};
