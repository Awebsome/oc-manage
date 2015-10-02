<?php namespace AWME\OctoManage\Classes;

/**
* Project Installer 
*/

use Flash;
use Config;
use Input;
use Request;

use DB;
use SSH;
use ApplicationException;
use Backend\Classes\Controller;

use Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;

use AWME\OctoManage\Models\Project;
use AWME\OctoManage\Models\OctoSettings;
use AWME\Engine\Models\EngineSettings;

use AWME\Git\Models\GitSettings;

use AWME\Git\Classes\Bitbucket;
use AWME\Git\Classes\Github;
use AWME\Git\Classes\Gitlab;

class Installer
{
    public function __construct(){

        /*
            Attrs from Project Model
         */
        $this->Project = Project::find(Request::input('Project.id'));
        
        $this->Bitbucket = (object) GitSettings::get('bitbucket');
        
        /**
         * Project Attrs
         * @var result
         */
        $this->project = (object) Request::input('Project');

        /**
         * Attribute changes
         */
        $this->project->name_prefixed = str_replace(':id', $this->Project->id, OctoSettings::get('name_prefix')).': '.$this->Project->name;
        $this->project->enabled = Request::input('Project.enabled', true);
        $this->project->maintenance = Request::input('Project.maintenance', false);

        $this->project->database = OctoSettings::get('db_prefix').Request::input('Project.database');
        $this->project->db_host = Request::input('Project.db_host', 'localhost');

    }
	
    /*
        Make Project folder.   
     */
    public function makeRepository()
    {
        $disk = Storage::disk('clients');
        if(!$disk->files($this->project->directory)){
            if($disk->makeDirectory($this->project->directory)){

                $repo = new Bitbucket();
                $repo->name         = $this->project->name_prefixed;
                $repo->owner        = strtolower($this->Bitbucket->username);
                $repo->repo_slug    = strtolower(str_replace('_','-', $this->project->directory));
                $repo->is_private   = true;
                $repo->fork_policy  = 'no_forks';
                
                $result = $repo->create();
                                
                if(!array_key_exists('error',json_decode($result)))
                { 

                    $git = new Bitbucket();
                    $git->repo         = strtolower(str_replace('_','-', $this->project->directory));
                    $git->owner        = strtolower($this->Bitbucket->username);
                    $git->directory    = OctoSettings::get('projects_path').'/'.$this->project->directory;
                    $git->gitClone();

                    return true;

                }else Flash::warning($result);
            }

        }else Flash::error('Project exists');
    }

    
    /**
     * [makeDatabase Create Database & user]
     * 
     */
    public function makeDatabase(){
        
        $disk = Storage::disk('clients');
        $query = DB::connection('engine');
        
        #Valida inexistencia de Usuario y Database para el proyecto 
        if(!$query->table('user')->where('user', $this->project->db_username)->get()){
            if(!$query->table('db')->where('db', $this->project->database)->get()){
                
                # ~ Crear base de datos
                $query->statement("CREATE DATABASE IF NOT EXISTS ".$this->project->database);
                $query->statement("CREATE USER '".$this->project->db_username."'@'".$this->project->db_host."' IDENTIFIED BY '".$this->project->db_password."'");
                $query->statement("GRANT ALL PRIVILEGES ON `".$this->project->database."`.* TO '".$this->project->db_username."'@'".$this->project->db_host."'");
                
                DB::disconnect('engine');
                return true;

            }else Flash::error("The database already exists");
        }else Flash::error("The user from database already exists");
    }

    /**
     * [makeProject composer create project]
     */
    public function makeProject(){

        $disk = Storage::disk('clients');
        
        #Comprueba inexistencia del proyecto
        if(!$disk->exists($this->project->directory.'/composer.json'))
        {
            
            $commands = [
                'cd '.OctoSettings::get('projects_path').'/'.$this->project->directory,
                'composer create-project october/october tmp-project dev-master',
                'mv tmp-project/* .',
                'mv tmp-project/.[^.]* .',
                'rm -rf tmp-project',
                'php artisan key:generate',
                'chown -hR www-data:www-data .',
                'chmod -R 777 storage'
            ];
            SSH::run($commands); 
            return true;

        }else Flash::error('Project Exists, delete before install again');
    }

    /**
     * makeConfigs
     * project/config/database.php Configs
     */
    public function makeConfigs(){

        $disk = Storage::disk('clients');
        $conf_path = $this->project->directory.'/config';

        if($disk->files($conf_path))
        {
            $dbFile = $conf_path.'/database.php';

            if ($disk->exists($dbFile))
            {
                $configs = $disk->get($dbFile);

                $OldConfig = [
                    'host'       => "/'host'(.*?)=> '(.*?)',/",
                    'port'       => "/'port'(.*?)=> '(.*?)',/",
                    'database'   => "/'database'(.*?)=> '(.*?)',/",
                    'username'   => "/'username'(.*?)=> '(.*?)',/",
                    'password'   => "/'password'(.*?)=> '(.*?)',/",
                    'charset'    => "/'charset'(.*?)=> '(.*?)',/",
                    'collation'  => "/'collation'(.*?)=> '(.*?)',/",
                    'prefix'     => "/'prefix'(.*?)=> '(.*?)',/",
                ];

                $NewConfig = [
                    'host'       => "'host'      => '".$this->project->db_host."',",
                    'port'       => "'port'      => '".EngineSettings::get('port')."',",
                    'database'   => "'database'  => '".$this->project->database."',",
                    'username'   => "'username'  => '".$this->project->db_username."',",
                    'password'   => "'password'  => '".$this->project->db_password."',",
                    'charset'    => "'charset'   => '".EngineSettings::get('charset','utf8')."',",
                    'collation'  => "'collation' => '".EngineSettings::get('collation','utf8_unicode_ci')."',",
                    'prefix'     => "'prefix'    => '".EngineSettings::get('prefix','')."',",
                ];

                $config_contents = preg_replace($OldConfig, $NewConfig, $configs);
                
                if($disk->put($dbFile,$config_contents))
                    return true;
                else Flash::error("An error has occurred in the configuration process");
            }
        }
    }

    /**
     * Make Install
     *
     * Install project
     */
    public function makeInstall(){

        $disk = Storage::disk('clients');
        
        if($disk->exists($this->project->directory.'/composer.json'))
        {
            $commands = [
                'cd '.OctoSettings::get('projects_path').'/'.$this->project->directory,
                'php artisan october:up',
                'chown -hR www-data:www-data .',
                'chmod -R 777 storage'
            ];

            SSH::run($commands);

            $project = Project::find($this->Project->id);
            if($project){
                $project->is_installed = 1;
                $project->save();

                return true;

            }else Flash::error("The project does not exist in the db, or is not accessible");

        }else Flash::error('Project Does not exists for make install');
    }

    /**
     * [makeAjenti Project In hosting]
     * Config domain
     */
    public function makeAjenti(){

        $disk = Storage::disk('engine');

        /*
            json "default initial config"
         */
        $ajentiJson = 'config/ajenti.json';
            
        if (!$disk->exists($ajentiJson))
        {
            Flash::error("Initial ajenti.json does not exists in Engine config");
        }else {
            
            /**
             * SET CONFIGS
             */
            Config::set('ajenti', get_object_vars(json_decode($disk->get($ajentiJson))));

            #Websites > Site > General > Name
            Config::set('ajenti.name', $this->project->name_prefixed);

            #Websites > Site > General > Maintenace Mode (true, false)
            Config::set('ajenti.maintenance_mode', $this->project->maintenance);

            #Websites > Site > Domains
            $domains = str_replace('*', $this->Project->slug_domain, OctoSettings::get('slug_domain')).'|'.$this->project->domains;
            foreach (explode('|', $domains) as $key => $domain) {
                $ajenti_domains[$key]['domain'] = $domain;
            }
            Config::set('ajenti.domains', $ajenti_domains);

            Config::set('ajenti.enabled', $this->project->enabled);
            Config::set('ajenti.root', OctoSettings::get('projects_path').'/'.$this->project->directory);
           

            #Websites > Site > Ftp
            Config::set('ajenti.extensions', [
                'ajenti.plugins.vh.processes.ProcessesExtension' => [
                                'processes' => [],
                    ],
                'ajenti.plugins.vh-pureftpd.pureftpd.PureFTPDExtension' => [
                                'username' => $this->project->ftp_login,
                                'password' => $this->project->ftp_password,
                                'created' => true,
                    ],
                'ajenti.plugins.vh-mysql.mysql.MySQLExtension' =>[
                    'users' => [
                        [
                            'password' => $this->project->db_password,
                            'name'     => $this->project->db_username,
                        ]
                    ],
                    'databases' => [
                        [
                            'name'  => $this->project->database,
                        ]
                    ]
                ]
            ]);
            
            # Set Configs
            $ajentiContents = json_encode(Config::get('ajenti'));

            # Open client ajenti.json
            $disk = Storage::disk('clients');

            # project/config folder
            $conf_path = $this->project->directory.'/config';

            if($disk->put($conf_path.'/ajenti.json', $ajentiContents))
               return true;
            else Flash::error("It failed to create the configuration Ajenti file");

        } #end else.
    }

    /**
     * makeAjentiReload 
     * Ajenti Load Configs Set Website.
     */
    public function makeAjentiReload(){

        self::makeAjenti();

        # Open client ajenti.json
        $disk = Storage::disk('clients');
        
        # project/config folder
        $conf_path = $this->project->directory.'/config';
        
        # projects_path/project/config/ajenti.json client file
        $ajenti_path = OctoSettings::get('projects_path').'/'.$conf_path.'/ajenti.json';

        if($disk->exists($conf_path.'/ajenti.json'))
        {
            $commands = [
                'ajenti-ipc v import '.$ajenti_path,
                'ajenti-ipc v apply',
                'sudo service php5-fpm restart',
                'sudo service nginx restart'
            ];

            Flash::success("Ajenti is reloaded successfully");
            
            SSH::run($commands);

        }else Flash::error("The ajenti.json project file, not found");
    }

    /**
     * delete 
     * make delete installation && database.
     * @return result
     */
    public function delete(){
        
        /**
         * Project Files Delete
         */
        if(is_dir(OctoSettings::get('projects_path').'/'.$this->project->directory)){
            $commands = [
                'cd '.OctoSettings::get('projects_path'),
                'rm -rf '.$this->project->directory,
            ];

            SSH::run($commands);
        }else Flash::info("Directory does not exists");
        
        /**
         * Database Delete
         */
        $query = DB::connection('engine');

        if(!$query->statement("DROP DATABASE IF EXISTS ".$this->project->database))
            Flash::info("The database could not be removed");

        if($query->table('user')->where('user', $this->project->db_username)->get()){
            if(!$query->statement("DROP USER '".$this->project->db_username."'@'".$this->project->db_host."'"))
               Flash::info("The User of database could not be removed");
        }

        if($this->Project){
            $project = Project::find($this->Project->id);
            $project->delete();

            DB::disconnect('engine');
            return true;
        }else Flash::error("The Project could not be removed");
    }
}