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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('contactid')->nullable();
            $table->string('locationId')->nullable();
            $table->string('contactName')->nullable();
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('companyName')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('dnd')->nullable();
            $table->string('type')->nullable();
            $table->string('source')->nullable();
            $table->string('assignedTo')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postalCode')->nullable();
            $table->string('address1')->nullable();
            $table->dateTime('dateAdded')->nullable();
            $table->dateTime('dateUpdated')->nullable();
            $table->dateTime('dateOfBirth')->nullable();
            $table->double('lastActivity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
    }
};
