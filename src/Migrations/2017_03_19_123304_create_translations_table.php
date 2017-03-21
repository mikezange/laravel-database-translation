<?php

namespace MikeZange\LaravelDatabaseTranslation\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateTranslationsTable.
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
