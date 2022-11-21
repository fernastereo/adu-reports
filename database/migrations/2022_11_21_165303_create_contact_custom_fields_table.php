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
        Schema::create('contact_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string("customFieldId")->nullable();
            $table->longText("value")->nullable();
            $table->unsignedBigInteger("contact_id");
            $table->timestamps();
            $table->foreign("contact_id")->references("id")->on("contacts")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_custom_fields');
    }
};
