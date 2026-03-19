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
        Schema::create('masjid', function (Blueprint $table) {
            $table->id();
            $table->string('nama_masjid', 255); //VARCHAR(255)
            $table->text('alamat');      //TEXT type
            $table->string('negeri', 100);      //VARCHAR(100)
            $table->string('contact_phone', 20);    //VARCHAR(20)
            $table->string('logo_url', 255)->nullable(); //VARCHAR(255) and nullable
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('masjid');
    }
};
