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
        Schema::table('efd_importacoes', function (Blueprint $table) {
            $table->text('arquivo_base64')->nullable()->after('filename');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('efd_importacoes', function (Blueprint $table) {
            $table->dropColumn('arquivo_base64');
        });
    }
};
