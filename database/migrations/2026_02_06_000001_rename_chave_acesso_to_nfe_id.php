<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xml_notas', fn (Blueprint $t) => $t->renameColumn('chave_acesso', 'nfe_id'));
        Schema::table('priv_cpf_operacoes', fn (Blueprint $t) => $t->renameColumn('chave_acesso', 'nfe_id'));
    }

    public function down(): void
    {
        Schema::table('xml_notas', fn (Blueprint $t) => $t->renameColumn('nfe_id', 'chave_acesso'));
        Schema::table('priv_cpf_operacoes', fn (Blueprint $t) => $t->renameColumn('nfe_id', 'chave_acesso'));
    }
};
