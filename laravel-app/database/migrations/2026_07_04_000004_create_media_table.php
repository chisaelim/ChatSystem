<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->enum('media_type', ['file', 'voice', 'video', 'image']); // image for thumbnails
            $table->string('original_name'); // Original filename
            $table->string('file_path'); // Path relative to storage
            $table->string('mime_type'); // e.g., audio/mpeg, video/mp4
            $table->unsignedBigInteger('file_size'); // In bytes
            $table->integer('duration')->nullable(); // In seconds (for voice/video)
            $table->string('url')->nullable(); // CDN or public URL
            $table->timestamps();

            $table->index('message_id');
            $table->index('media_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
