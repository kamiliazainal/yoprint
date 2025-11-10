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
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->string('status')->default('processing')->after('file_size'); // processing, completed, failed
            $table->longText('error_message')->nullable()->after('status');
            $table->integer('rows_processed')->default(0)->after('error_message');
            $table->integer('rows_inserted')->default(0)->after('rows_processed');
            $table->integer('rows_updated')->default(0)->after('rows_inserted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->dropColumn(['status', 'error_message', 'rows_processed', 'rows_inserted', 'rows_updated']);
        });
    }
};
