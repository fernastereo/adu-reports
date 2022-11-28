<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointment_attachments', function (Blueprint $table) {
            $table->id();
            $table->longText("value")->nullable();
            $table->unsignedBigInteger("appointment_id");
            $table->timestamps();
            $table->foreign("appointment_id")->references("id")->on("appointments")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('appointment_attachments');
    }
};
