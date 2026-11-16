<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carousel_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image_path');
            $table->string('link_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->string('caption')->nullable();
            $table->string('image_path');
            $table->date('taken_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // key-value untuk teks yang bisa diedit admin: visi, misi, sejarah, alur_pendaftaran, kontak
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->longText('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('carousel_slides');
    }
};
