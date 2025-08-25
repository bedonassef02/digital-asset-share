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
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['name', 'description', 'mime_type', 'size', 'extension']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('extension');
        });
    }
};
