<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * マイグレーションを実行
     */
    public function up(): void
    {
        Schema::create('redmine_statuses', function (Blueprint $table) {
            $table->id();
            $table->integer('redmine_id')->nullable()->index();
            $table->string('name');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            
            $table->unique(['name']);
        });
    }

    /**
     * マイグレーションをロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('redmine_statuses');
    }
};
