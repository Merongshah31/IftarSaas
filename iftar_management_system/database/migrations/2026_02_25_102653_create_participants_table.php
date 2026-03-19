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
        Schema::create('participants', function (Blueprint $table) {
            $table->id(); //Primary key
            $table->foreignId('iftar_day_id')->constrained('iftar_days')->onDelete('cascade'); //foreign key referencing iftar_days table with cascade on delete
            //[participant details]
            $table->string('nama', 255); //varchar(255)
            $table->string('no_telefon', 20); //varchar(20)
            $table->unsignedInteger('bil_pax')->default(1); //unsigned int default 1
            
            //cheked status pending/checkin/noshow , default pending
            $table->enum('checkin_status', ['PENDING', 'CHECKED_IN', 'NO_SHOW'])->default('PENDING');

            //boolean, default false
            $table->boolean('reminder_sent')->default(false);
            $table->boolean('is_cancelled')->default(false);

            //timestamp, nullable
            $table->timestamp('cancelled_at')->nullable();

            $table->unsignedInteger('cancellation_count')->default(0); //track how many times user cancelled(max3)

            $table->text('notes')->nullable(); //text nullable

            $table->timestamps();


            //indexes:
            //fk to iftar_Days
            
            $table->index('is_cancelled');
            $table->index('no_telefon');
            $table->index('checkin_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
