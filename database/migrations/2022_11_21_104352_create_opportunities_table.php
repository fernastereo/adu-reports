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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('opportunityid')->nullable();
            $table->string('name')->nullable();
            $table->double('monetaryValue')->nullable();
            $table->string('pipelineId')->nullable();
            $table->string('pipelineStageId')->nullable();
            $table->string('pipelineStageUId')->nullable();
            $table->string('assignedTo')->nullable();
            $table->string('status')->nullable();
            $table->dateTime('lastStatusChangeAt')->nullable();
            $table->dateTime('createdAt')->nullable();
            $table->dateTime('updatedAt')->nullable();
            $table->string('contactid')->nullable();
            $table->string('contactname')->nullable();
            $table->string('contactemail')->nullable();
            $table->string('contactphone')->nullable();
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
        Schema::dropIfExists('opportunities');
    }
};
