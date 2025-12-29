<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('Space_whapichannel_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); 
            $table->unsignedBigInteger('space_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_origin')->nullable();
            $table->string('payment_reference')->nullable();
            $table->integer('payment_amount')->nullable();
            $table->boolean('_isPremium')->default(false);
            $table->string('instance_id')->unique();
            $table->bigInteger('creationTS');
            $table->string('ownerId');
            $table->bigInteger('activeTill');
            $table->string('token');
            $table->integer('server');
            $table->boolean('stopped')->default(false);
            $table->string('status');
            $table->string('name');
            $table->string('projectId');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('space_whapi_channel_details');
    }
};
