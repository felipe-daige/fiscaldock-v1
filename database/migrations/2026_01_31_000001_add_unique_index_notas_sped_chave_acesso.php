<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared("
            CREATE OR REPLACE FUNCTION efd_notas_ignore_duplicate()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM efd_notas
                    WHERE cliente_id   = NEW.cliente_id
                      AND chave_acesso = NEW.chave_acesso
                      AND modelo       = NEW.modelo
                      AND numero       = NEW.numero
                      AND serie        = NEW.serie
                ) THEN
                    RETURN NULL;
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        DB::unprepared("
            DROP TRIGGER IF EXISTS trg_efd_notas_ignore_duplicate ON efd_notas;
            CREATE TRIGGER trg_efd_notas_ignore_duplicate
                BEFORE INSERT ON efd_notas
                FOR EACH ROW EXECUTE FUNCTION efd_notas_ignore_duplicate();
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS trg_efd_notas_ignore_duplicate ON efd_notas');
        DB::unprepared('DROP FUNCTION IF EXISTS efd_notas_ignore_duplicate()');
    }
};
