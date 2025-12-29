<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class space_iq_docs extends Migration
{

    public function up(): void
    {
        Schema::create('spaces_iq_docs',function(Blueprint $table){
            $table->id();
            $table->unsignedBigInteger('space_id');
            $table->string('file_path')->nullable();         
            $table->string('file_name')->nullable();          
            $table->string('mime_type')->nullable(); 
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
