<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('broadcast_headers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('frequency')->nullable(); 
            $table->text('content')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_headers');
    }
};
