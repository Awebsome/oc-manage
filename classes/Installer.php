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

use AWME\Git\Models\GitSettings;

use AWME\Git\Classes\Bitbucket;
use AWME\Git\Classes\Github;
use AWME\Git\Classes\Gitlab;

class Installer
{
    public function __construct(){

        $this->project = Project::find(Request::input('Project.id'));
        $this->Bitbucket = (object) GitSettings::get('bitbucket');
    }
	
    /*
        Make Project folder.   
     */
    public function makeRepository()
    {
        $disk = Storage::disk('clients');
        if(!$disk->files(Request::input('Project.directory'))){
            if($disk->makeDirectory(Request::input('Project.directory'))){

                $repo = new Bitbucket();
                $repo->name         = Request::input('Project.name');
                $repo->owner        = strtolower($this->Bitbucket->username);
                $repo->repo_slug    = strtolower(str_replace('_','-', Request::input('Project.directory')));
                $repo->is_private   = true;
                $repo->fork_policy  = 'no_forks';
                
                $result = $repo->create();
                                
                if(!array_key_exists('error',json_decode($result)))
                { 

                    $git = new Bitbucket();
                    $git->repo         = strtolower(str_replace('_','-', Request::input('Project.directory')));
                    $git->owner        = strtolower($this->Bitbucket->username);
                    $git->directory    = OctoSettings::get('projects_path').'/'.Request::input('Project.directory');
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
        if(!$query->table('user')->where('user', Request::input('Project.username'))->get()){
            if(!$query->table('db')->where('db', Request::input('Project.database'))->get()){
                
                # ~ Crear base de datos
                $query->statement("CREATE DATABASE IF NOT EXISTS ".Request::input('Project.database'));
                $query->statement("CREATE USER '".Request::input('Project.username')."'@'".Request::input('Project.host')."' IDENTIFIED BY '".Request::input('Project.password')."'");
                $query->statement("GRANT ALL PRIVILEGES ON `".Request::input('Project.database')."`.* TO '".Request::input('Project.username')."'@'".Request::input('Project.host')."'");
                
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
        if(!$disk->exists(Request::input('Project.directory').'/composer.json'))
        {
            
            $commands = [
                'cd '.OctoSettings::get('projects_path').'/'.Request::input('Project.directory'),
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
        $conf_path = Request::input('Project.directory').'/config';

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
                    'host'       => "'host'      => '".Request::input('Project.host','localhost')."',",
                    'port'       => "'port'      => '".Request::input('Project.port','')."',",
                    'database'   => "'database'  => '".Request::input('Project.database')."',",
                    'username'   => "'username'  => '".Request::input('Project.username')."',",
                    'password'   => "'password'  => '".Request::input('Project.password')."',",
                    'charset'    => "'charset'   => '".Request::input('Project.charset','utf8')."',",
                    'collation'  => "'collation' => '".Request::input('Project.collation','utf8_unicode_ci')."',",
                    'prefix'     => "'prefix'    => '".Request::input('Project.prefix','')."',",
                ];

                $config_contents = preg_replace($OldConfig, $NewConfig, $configs);
                
                if($disk->put($dbFile,$config_contents))
                    return true;
                else Flash::error("An error has occurred in the configuration process");
            }
        }
    }

    /**
     * [makeInstall composer create project]
     */
    public function makeInstall(){

        $disk = Storage::disk('clients');
        
        if($disk->exists(Request::input('Project.directory').'/composer.json'))
        {
            $commands = [
                'cd '.OctoSettings::get('projects_path').'/'.Request::input('Project.directory'),
                'php artisan october:up'
            ];

            SSH::run($commands);

            $project = Project::find($this->project->id);
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
            Config::set('ajenti.name', Request::input('Project.name', $this->project->name));

            #Websites > Site > General > Maintenace Mode (true, false)
            Config::set('ajenti.maintenance_mode', Request::input('Project.maintenance', false));

            #Websites > Site > Domains
            $domains = str_replace('*', Request::input('Project.slug_domain', $this->project->slug_domain), OctoSettings::get('slug_domain')).'|'.Request::input('Project.domains');
            foreach (explode('|', $domains) as $key => $domain) {
                $ajenti_domains[$key]['domain'] = $domain;
            }
            Config::set('ajenti.domains', $ajenti_domains);

            Config::set('ajenti.enabled', Request::input('Project.enabled', true));
            Config::set('ajenti.root', OctoSettings::get('projects_path').'/'.Request::input('Project.directory'));
           

            #Websites > Site > Ftp
            Config::set('ajenti.extensions', [
                'ajenti.plugins.vh.processes.ProcessesExtension' => [
                                'processes' => [],
                    ],
                'ajenti.plugins.vh-pureftpd.pureftpd.PureFTPDExtension' => [
                                'username' => Request::input('Project.ftpuser'),
                                'password' => Request::input('Project.ftppsw'),
                                'created' => true,
                    ],
                'ajenti.plugins.vh-mysql.mysql.MySQLExtension' =>[
                    'users' => [
                        [
                            'password' => Request::input('Project.password'),
                            'name'     => Request::input('Project.username'),
                        ]
                    ],
                    'databases' => [
                        [
                            'name'  => Request::input('Project.database'),
                        ]
                    ]
                ]
            ]);
            
            # Set Configs
            $ajentiContents = json_encode(Config::get('ajenti'));

            # Open client ajenti.json
            $disk = Storage::disk('clients');

            # project/config folder
            $conf_path = Request::input('Project.directory').'/config';

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
        $conf_path = Request::input('Project.directory').'/config';
        
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
        if(is_dir(OctoSettings::get('projects_path').'/'.Request::input('Project.directory'))){
            $commands = [
                'cd '.OctoSettings::get('projects_path'),
                'rm -rf '.Request::input('Project.directory'),
            ];

            SSH::run($commands);
        }else Flash::info("Directory does not exists");
        
        /**
         * Database Delete
         */
        $query = DB::connection('engine');

        if(!$query->statement("DROP DATABASE IF EXISTS ".Request::input('Project.database')))
            Flash::info("The database could not be removed");

        if($query->table('user')->where('user', Request::input('Project.username'))->get()){
            if(!$query->statement("DROP USER '".Request::input('Project.username')."'@'".Request::input('Project.host')."'"))
               Flash::info("The User of database could not be removed");
        }

        if(Project::find($this->project->id)){
            $project = Project::find($this->project->id);
            $project->delete();
            return true;
        }else Flash::error("The Project could not be removed");
    }
}