<?php

namespace MikeZange\LaravelDatabaseTranslation\Migrations;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateTranslationsTable
 */
class CreateTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('database.translations.table'), function (Blueprint $table) {
            $table->increments('id');
            $table->string('namespace')->nullable();
            $table->string('group')->nullable();
            $table->string('key')->nullable();
            $table->text('values')->nullable();
            $table->timestamps();
            $table->unique(['namespace', 'group', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
