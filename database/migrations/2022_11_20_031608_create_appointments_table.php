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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string("appointmentid")->nullable();
            $table->string("selectedTimezone")->nullable();
            $table->longText("notes")->nullable();
            $table->string("contactId")->nullable();
            $table->string("locationId")->nullable();
            $table->boolean("isFree")->nullable();
            $table->string("title")->nullable();
            $table->boolean("isRecurring")->nullable();
            $table->string("address")->nullable();
            $table->string("assignedUserId")->nullable();
            $table->string("calendarId")->nullable();
            $table->string("appoinmentStatus")->nullable();
            $table->string("calendarProviderId")->nullable();
            $table->string("userCalendarId")->nullable();
            $table->string("status")->nullable();
            $table->string("appointmentStatus")->nullable();
            $table->dateTime("appointmentstartTime")->nullable();
            $table->dateTime("appointmentendTime")->nullable();
            $table->dateTime("appointmentcreatedAt")->nullable();
            $table->dateTime("appointmentupdatedAt")->nullable();
            $table->string("contactfirstName")->nullable();
            $table->string("contactemail")->nullable();
            $table->string("contactfingerprint")->nullable();
            $table->string("contactfirstNameLowerCase")->nullable();
            $table->string("contactfullNameLowerCase")->nullable();
            $table->string("contacttimezone")->nullable();
            $table->string("contactemailLowerCase")->nullable();
            $table->string("contactlastName")->nullable();
            $table->string("contactlocationId")->nullable();
            $table->string("contactcountry")->nullable();
            $table->string("contactphone")->nullable();
            $table->string("contactlastNameLowerCase")->nullable();
            $table->string("contacttype")->nullable();
            $table->dateTime("contactdateAdded")->nullable();
            $table->string("contactpostalCode")->nullable();
            $table->string("contactsource")->nullable();
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
        Schema::dropIfExists('appointments');
    }
};
