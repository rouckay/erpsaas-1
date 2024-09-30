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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('father_name');
            $table->string('gender')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('tazkira')->nullable();
            $table->string('degree')->nullable();
            $table->string('field_of_education')->nullable();
            $table->string('name_of_university')->nullable();
            $table->string('work_experience')->nullable();
            $table->string('name_of_organization')->nullable();
            $table->string('position')->nullable();
            $table->string('duration')->nullable();
            $table->string('ref1')->nullable();
            $table->string('ref2')->nullable();
            $table->string('ref3')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
