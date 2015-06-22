<?php namespace AWME\OctoManage;

use System\Classes\PluginBase;

use App;
use Config;
use Backend;

use AWME\Engine\Models\SshSettings;
use AWME\Engine\Models\EngineSettings;
use AWME\OctoManage\Models\OctoSettings;
use Illuminate\Foundation\AliasLoader;

/**
 * OctoManage Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = [
        'AWME.Engine',
        'AWME.Git',
    ];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'OctoManage',
            'description' => 'Self-hosted open source manager for maintaining your OctoberCMS sites from one location.',
            'author'      => 'AWME, LucasZdv',
            'homepage'    => 'http://awebsome.me',
            'icon'        => 'icon-leaf'
        ];
    }
    
    /**
    * Boot method, called right before the request route.
    */
    public function boot()
    {   
        /**
         * Register "/config/remote.php" for SSH
        */
        Config::set('remote.default', 'production');
        Config::set('remote.connections.production.host',       SshSettings::get('host'));
        Config::set('remote.connections.production.username',   SshSettings::get('username'));
        Config::set('remote.connections.production.password',   SshSettings::get('password'));
        Config::set('remote.connections.production.key',        SshSettings::get('key'));
        Config::set('remote.connections.production.keyphrase',  SshSettings::get('keyphrase'));
        Config::set('remote.connections.production.root',       SshSettings::get('root'));
        
        Config::set('filesystems.disks.engine.driver',          'local');
        Config::set('filesystems.disks.engine.root',            base_path());
        
        Config::set('filesystems.disks.clients.driver',         'local');
        Config::set('filesystems.disks.clients.root',           OctoSettings::get('projects_path'));

        Config::set('database.connections.engine.host',         EngineSettings::get('host'));
        Config::set('database.connections.engine.port',         EngineSettings::get('port'));
        Config::set('database.connections.engine.driver',       EngineSettings::get('driver'));
        Config::set('database.connections.engine.database',     EngineSettings::get('database'));
        Config::set('database.connections.engine.username',     EngineSettings::get('username'));
        Config::set('database.connections.engine.password',     EngineSettings::get('password'));
        Config::set('database.connections.engine.charset',      EngineSettings::get('charset'));
        Config::set('database.connections.engine.collation',    EngineSettings::get('collation'));
        Config::set('database.connections.engine.prefix',       EngineSettings::get('prefix'));

        // Register ServiceProviders
        App::register('Collective\Remote\RemoteServiceProvider');

        // Register aliases
        $alias = AliasLoader::getInstance();
        $alias->alias('SSH', 'Collective\Remote\RemoteFacade');
    }

    /**
     * Register Permissions
     * 
     */
    public function registerPermissions()
    {
        return [
            'awme.octomanage.settings'   => ['tab' => 'OctoManage','label' => 'OctoManage Settings'],
            'awme.octomanage.read_projects'   => ['tab' => 'OctoManage','label' => 'View Projects']
        ];
    }
    
    /**
     * Register Backend Navigation Menu
     * 
     */
    public function registerNavigation()
    {
         return [
            'octomanage' => [
                'label'       => OctoSettings::get('octo_label','OctoManage'),
                'url'         => Backend::url('awme/octomanage/projects'),
                'icon'        => 'icon-leaf',
                'order'       => 999,

                'sideMenu' => [

                    'projects' =>   [
                        'label'       => 'Projects',
                        'icon'        => 'icon-sitemap',
                        'url'         => Backend::url('awme/octomanage/projects'),
                        'permissions' => ['awme.octomanage.read_projects']
                    ]

                ]
            ]
        ];
    }

    /**
     * Register Backend Plugin Settings
     * 
     */
    public function registerSettings()
    {
        return [

            //OctoManage Settings
            'octomanage'  => [
                'label'       => 'OctoManage',
                'description' => 'Settings of OctoManage Plguin',
                'category'    => 'OctoManage',
                'icon'        => 'icon-leaf',
                'class'       => 'AWME\OctoManage\Models\OctoSettings',
                'order'       => 410,
                'permissions' => [ 'awme.octomanage.settings' ],
                'keywords'    => 'octomanage, octo'
            ]
        ];
    }
}
