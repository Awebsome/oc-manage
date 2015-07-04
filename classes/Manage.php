<?php namespace AWME\OctoManage\Classes;

/**
* Manage Project
*/

use DB;
use Hash;
use Flash;
use Config;
use Request;

use AWME\OctoManage\Models\Project;
use AWME\OctoManage\Models\OctoSettings;

class Manage
{
	public function __construct(){

        $this->Project = Project::find(Request::input('Project.id'));
    }

	/*
	  |	Set Default Installation Password
	*/
	public function setPassword(){
        
        # Set Project Database.
        Config::set('database.connections.engine.database', OctoSettings::get('db_prefix').$this->Project->database);
        
        $database = DB::connection('engine');
        
        $user = $database->table('backend_users')
        	->where('id', 1)
        	->update([
        		'first_name'=> OctoSettings::get('backend_first_name'),
        		'last_name'=> OctoSettings::get('backend_last_name'),
        		'email'=> OctoSettings::get('backend_email'),
        		'login'=> OctoSettings::get('backend_login'),
        		'password'=> Hash::make(OctoSettings::get('backend_password'))
        	]);

        DB::disconnect('engine');
        
        return $user;
    }
}