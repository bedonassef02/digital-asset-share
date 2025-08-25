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
        Schema::table('asset_views', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->renameColumn('asset_id', 'viewable_id');
            $table->string('viewable_type')->after('viewable_id');
            $table->index(['viewable_id', 'viewable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_views', function (Blueprint $table) {
            $table->dropIndex(['viewable_id', 'viewable_type']);
            $table->dropColumn('viewable_type');
            $table->renameColumn('viewable_id', 'asset_id');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
        });
    }
};
