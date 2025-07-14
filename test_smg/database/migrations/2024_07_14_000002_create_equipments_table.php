<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('slot', ['head', 'hand', 'shield', 'body', 'shoes', 'accessory', 'bag']);
            $table->integer('attack')->default(0);
            $table->integer('defense')->default(0);
            $table->integer('speed')->default(0);
            $table->integer('evasion')->default(0);
            $table->integer('hp')->default(0);
            $table->integer('mp')->default(0);
            $table->integer('accuracy')->default(0);
            $table->string('effect')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('equipments');
    }
};