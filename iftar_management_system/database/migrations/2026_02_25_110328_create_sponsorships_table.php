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
        Schema::create('sponsorships', function (Blueprint $table) {
            $table->id(); //Primary key
            //masjid_id fk
            $table->foreignId('masjid_id')->constrained('masjid')->onDelete('cascade'); //foreign key referencing masjid table with cascade on delete
            //iftar_day_id fk, nullable
            $table->foreignId('iftar_day_id')->nullable()->constrained('iftar_days')->onDelete('set null'); //foreign key referencing iftar_days table with set null on delete

            //details sponsor
            $table->string('nama_sponsor', 255);  //varchar(255)
            $table->string('phone', 20); //varchar(20)
            //jumlah tajaan  decimal 10,2
            $table->decimal('jumlah_tajaan', 10, 2);
            //jenis tajaan enum (moreh/iftar)
            $table->enum('jenis_tajaan', ['MOREH', 'IFTAR']);
            $table->enum('sponsor_coverage', ['FULL', 'PARTIAL', 'GENERAL']); //sponsor coverage enum (full/partial) default partial
            //payment status enum (pending/paid/failed) default pending
            $table->enum('payment_status', ['PENDING', 'PAID', 'FAILED'])->default('PENDING');
            //notes text nullable
            $table->text('notes')->nullable();
            $table->timestamps();

            //indexes
            $table->index('payment_status');
            $table->index('jenis_tajaan');
            

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsorships');
    }
};
