<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBotstorageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot-storage', function (Blueprint $table) {
            $table->increments('id');
            
            $table->integer('user_id')->unsigned();
            
            $table->integer('chat_id');
            $table->string('key')->nullable();
            $table->string('data');
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
        Schema::drop('bot-storage');
    }
}
