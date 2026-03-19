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
        Schema::create('iftar_days', function (Blueprint $table) {
            //Primary key
            $table->id();
            //foreign key
            $table->foreignId('masjid_id')->constrained('masjid')->onDelete('cascade'); //foreign key referencing masjid table with cascade on delete
            
            //fields
            $table->date('tarikh'); //DATE
            $table->unsignedInteger('kapasiti_max'); //Int unsigned
            $table->unsignedInteger('jumlah_daftar')->default(0); //unsigned int
            $table                                                         //status open/full/closed , default open
                ->enum('status', ['open', 'full', 'closed'])
                ->default('open');

            $table->text('notes')->nullable(); //TEXT and nullable

            //timestamps for created_at and updated_at
            $table->timestamps();

            $table->unique(['masjid_id', 'tarikh'], 'unique_masjid_date'); // Unique constraint on masjid_id and tarikh
            
            $table ->index('status'); // Index on status for faster queries
            $table ->index('tarikh'); // Index on tarikh for faster queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iftar_days');
    }
};
