<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'note_user_id')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('note_id')->unique()->index();
            $table->uuid('duplicate_id')->nullable();
            $table->foreignId('category_id')->constrained('categories', 'category_id')->onUpdate('cascade')->onDelete('cascade');
            $table->string('title');
            $table->text('note_content');
            $table->foreignId('star_note_id')->unique()->index();
            $table->timestamps();
            $table->SoftDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
