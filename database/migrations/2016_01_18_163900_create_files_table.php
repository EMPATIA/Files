<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key');
            $table->string('user_key');
            $table->string('name');            
            $table->string('file');
            $table->string('type');             
            $table->integer('size');     
            $table->string('code');
            $table->boolean('public')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('files');
    }
}
