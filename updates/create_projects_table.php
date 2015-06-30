<?php namespace AWME\OctoManage\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProjectsTable extends Migration
{

    public function up()
    {
        Schema::create('awme_octomanage_projects', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('company');
            $table->string('slug_domain');
            $table->string('domains');
            $table->string('theme');
            $table->string('git');
            $table->string('plugins');
            $table->string('host');
            $table->string('database');
            $table->string('username');
            $table->string('password');
            $table->string('ftphost');
            $table->string('directory');
            $table->string('ftpuser');
            $table->string('ftppsw');
            $table->boolean('is_installed');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('awme_octomanage_projects');
    }

}
