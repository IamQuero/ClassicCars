<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->string('generation')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('mileage');
            $table->string('engine')->nullable();
            $table->unsignedSmallInteger('horsepower')->nullable();
            $table->enum('transmission', ['manual', 'automatic']);
            $table->enum('fuel', ['petrol', 'diesel', 'electric', 'hybrid']);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
