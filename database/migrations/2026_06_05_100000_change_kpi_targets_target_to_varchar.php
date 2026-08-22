<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert kpi_targets.target from DECIMAL(15,2) → VARCHAR(50).
 *
 * Value fields in the v2 mobile contract (API_REFERENCE §11.4.4–§11.4.7) are
 * opaque strings — store and echo verbatim, no float cast or trailing-zero
 * cleanup. With the column as DECIMAL, MySQL serialises "120" as "120.00",
 * forcing the application to either echo ".00" or strip trailing zeros (which
 * mangles legitimate values like "120" → "12"). Switching to VARCHAR lets us
 * persist exactly what the user typed.
 *
 * Existing rows convert losslessly — "120.00" stays as "120.00" in the string
 * column. New writes from v2 store the wire string verbatim.
 *
 * The web app's KpiController::saveTarget assigns the form input directly to
 * the column, so it keeps working unchanged. MySQL coerces VARCHAR → numeric
 * for any web-side aggregation queries.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kpi_targets')) {
            return;
        }

        // Use raw SQL to avoid the doctrine/dbal dependency Laravel needs for
        // Schema::->change() on type changes.
        DB::statement('ALTER TABLE kpi_targets MODIFY COLUMN target VARCHAR(50) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('kpi_targets')) {
            return;
        }

        DB::statement('ALTER TABLE kpi_targets MODIFY COLUMN target DECIMAL(15,2) NULL');
    }
};
