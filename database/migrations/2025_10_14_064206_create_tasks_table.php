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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            // Görevin sahibi kullanıcı
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Temel alanlar
            $table->string('title');
            $table->text('description')->nullable();

            // Durum ve planlama
            $table->boolean('is_completed')->default(false)->index();
            $table->dateTime('due_at')->nullable()->index();

            // Görsel/sıralama ihtiyaçları için isteğe bağlı öncelik ve sıralama
            $table->unsignedTinyInteger('priority')->default(0)->index();
            $table->unsignedInteger('sort_order')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
