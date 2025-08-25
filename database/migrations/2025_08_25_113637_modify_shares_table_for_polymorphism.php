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
        Schema::table('shares', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->renameColumn('asset_id', 'shareable_id');
            $table->string('shareable_type')->after('shareable_id');
            $table->index(['shareable_id', 'shareable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropIndex(['shareable_id', 'shareable_type']);
            $table->dropColumn('shareable_type');
            $table->renameColumn('shareable_id', 'asset_id');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
        });
    }
};