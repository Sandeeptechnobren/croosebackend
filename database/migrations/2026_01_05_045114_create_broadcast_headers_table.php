<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_headers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('frequency')->nullable();
            $table->text('content')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('target_id')->references('id')->on('target_messages')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('deleted_by')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_headers');
    }
};
