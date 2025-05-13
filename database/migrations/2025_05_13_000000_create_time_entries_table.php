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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->integer('redmine_id')->unique();
            $table->integer('user_id');
            $table->string('user_name');
            $table->integer('issue_id');
            $table->string('issue_subject')->nullable();
            $table->float('hours');
            $table->date('spent_on');
            $table->text('comments')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('issue_id');
            $table->index('spent_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
