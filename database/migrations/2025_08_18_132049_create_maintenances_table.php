<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('maintenance_item_id');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('maintenance_item_id')->references('id')->on('maintenance_items')->onDelete('cascade');
            $table->string('mileage_at_service');
            $table->date('service_date');
            // valorizado del servicio
            $table->decimal('labor_cost', 8, 2);
            $table->decimal('parts_cost', 8, 2);
            $table->decimal('extra_cost', 8, 2)->nullable();
            $table->decimal('total_cost', 8, 2);
            // Pastillas de freno delanteras
            $table->integer('front_left_brake_pad')->nullable();
            $table->integer('front_right_brake_pad')->nullable();
            // Pastillas de freno traseras
            $table->integer('rear_left_brake_pad')->nullable();
            $table->integer('rear_right_brake_pad')->nullable();
            // Fecha de último registro
            $table->string('progres_bar');
            $table->text('notes_valorization')->nullable();
            $table->string('photo')->nullable();
            $table->string('file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
