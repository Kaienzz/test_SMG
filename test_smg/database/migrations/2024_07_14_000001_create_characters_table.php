<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('hp')->default(100);
            $table->integer('mp')->default(10);
            $table->integer('attack')->default(10);
            $table->integer('defense')->default(10);
            $table->integer('speed')->default(10);
            $table->integer('evasion')->default(5);
            $table->integer('accuracy')->default(10);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};