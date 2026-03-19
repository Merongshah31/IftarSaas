<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::table('participants', function (Blueprint $table) {
            if (!Schema::hasColumn('participants', 'masjid_id')) {
                $table->unsignedBigInteger('masjid_id')->nullable()->after('id');
            }
        });

        if ($driver === 'mysql') {
            DB::statement('UPDATE participants p INNER JOIN iftar_days i ON i.id = p.iftar_day_id SET p.masjid_id = i.masjid_id WHERE p.masjid_id IS NULL');
            DB::statement('ALTER TABLE participants MODIFY masjid_id BIGINT UNSIGNED NOT NULL');
        } else {
            DB::statement('UPDATE participants SET masjid_id = (SELECT masjid_id FROM iftar_days WHERE iftar_days.id = participants.iftar_day_id) WHERE masjid_id IS NULL');
        }

        Schema::table('participants', function (Blueprint $table) {
            $table->foreign('masjid_id')->references('id')->on('masjid')->onDelete('cascade');
            $table->unique(['masjid_id', 'iftar_day_id', 'no_telefon'], 'participants_masjid_day_phone_unique');
            $table->index('masjid_id', 'participants_masjid_id_index');
        });

        // Normalize existing values before enum conversion.
        if ($driver === 'mysql') {
            DB::statement("UPDATE iftar_days SET status = UPPER(status)");
            DB::statement("ALTER TABLE iftar_days MODIFY status ENUM('OPEN','FULL','CLOSED') NOT NULL DEFAULT 'OPEN'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE iftar_days MODIFY status ENUM('open','full','closed') NOT NULL DEFAULT 'open'");
            DB::statement("UPDATE iftar_days SET status = LOWER(status)");
        }

        Schema::table('participants', function (Blueprint $table) {
            $table->dropUnique('participants_masjid_day_phone_unique');
            $table->dropForeign(['masjid_id']);
            $table->dropIndex('participants_masjid_id_index');
            $table->dropColumn('masjid_id');
        });
    }
};
