<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->enum('doc_type', ['cv', 'ktp', 'transkrip', 'surat_pengantar']);
            $table->string('file_path');                 // simpan di disk PRIVATE, bukan public
            $table->string('original_name')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'doc_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
