<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpacesTable extends Migration
{
    public function up()
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('name');
            $table->string('chatbot_name');
            $table->string('space_phone');
            $table->boolean('is_active')->default(1); 
            $table->string('category');
            $table->string('currency')->nullable();
            $table->string('image')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->timestamp('last_update')->nullable();
            $table->softDeletes();
            $table->timestamps();
            // Foreign key constraint
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('spaces');
    }
}
