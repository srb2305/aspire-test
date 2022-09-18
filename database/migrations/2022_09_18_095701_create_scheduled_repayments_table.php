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
        Schema::create('scheduled_repayments', function (Blueprint $table) {
            $table->id();
            $table->integer('loan_id');
            $table->date('due_date')->nullable();
            $table->float('due_amount',8, 2)->nullable();
            $table->float('paid_amount',8, 2)->nullable();
            $table->string('state')->default('PENDING')->comment('PENDING,PAID');
            $table->timestampTz('payment_datetime')->nullable();
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
        Schema::dropIfExists('scheduled_repayments');
    }
};
