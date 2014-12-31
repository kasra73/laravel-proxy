<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCookiesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cookies', function($table)
        {
            $table->bigIncrements('id');
            $table->string('userID');
            $table->text('comment');
            $table->string('domain');
            $table->boolean('httponly');
            $table->string('expires');
            $table->string('key');
            $table->string('path');
            $table->boolean('secure');
            $table->string('value');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('cookies');
	}

}
