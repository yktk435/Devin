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
        Schema::create('redmine_users', function (Blueprint $table) {
            $table->id();
            $table->integer('redmine_id')->unique();
            $table->string('name');
            $table->timestamps();
            
            $table->index('redmine_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redmine_users');
    }
};
