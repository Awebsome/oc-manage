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
            
            #Installation
            $table->string('name');
            $table->string('company');
            $table->string('slug_domain');
            $table->string('domains');
            $table->string('theme');
            $table->string('git');
            $table->string('plugins');
            $table->string('host');
            
            #Database Configs
            $table->string('database');
            $table->string('db_username');
            $table->string('db_password');

            #FTP Configs
            $table->string('ftp_login');
            $table->string('ftp_password');
            $table->string('directory');
            
            $table->boolean('is_installed');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('awme_octomanage_projects');
    }

}
