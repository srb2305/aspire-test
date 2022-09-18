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
        Schema::create('customer_loans', function (Blueprint $table) {
            $table->id();
            $table->integer('loan_id')->unique();
            $table->integer('user_id');
            $table->float('loan_amout',8, 2)->nullable();
            $table->float('loan_pending_amout',8, 2)->nullable();
            $table->integer('term')->nullable();
            $table->string('state')->default('PENDING')->comment('PENDING,APPROVED');
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
        Schema::dropIfExists('customer_loans');
    }
};
