<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class space_iq extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
        {
            Schema::create('space_iq', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('space_id');
                $table->unsignedBigInteger('client_id'); 
                $table->text('prompt_content');
                $table->json('attachments')->nullable(); 
                $table->timestamps();

                $table->foreign('space_id')->references('id')->on('spaces')->onDelete('cascade');
            });
        }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
