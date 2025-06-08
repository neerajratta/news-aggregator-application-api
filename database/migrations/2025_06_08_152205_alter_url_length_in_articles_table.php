<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUrlLengthInArticlesTable extends Migration
{
    public function up()
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->text('url')->change(); // change VARCHAR(255) to TEXT
        });
    }

    public function down()
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('url', 255)->change();
        });
    }
}

