<?php namespace AWME\OctoManage\Controllers;

use Flash;
use Request;
use BackendMenu;
use Backend\Classes\Controller;

use AWME\OctoManage\Classes\Installer as Install;
use AWME\OctoManage\Models\Project;
use AWME\OctoManage\Models\OctoSettings;

use Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;

/**
 * Project Back-end Controller
 */
class Projects extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('AWME.OctoManage', 'octomanage', 'projects');
    }

    public function onMakeIntallation(){

        $project = new Install;
          if($project->makeRepository())
            if($project->makeDatabase())
             if($project->makeProject())
              if($project->makeConfigs())
               if($project->makeInstall())
                if($project->makeAjenti())
                    Flash::success("Installation Complete");
                else Flash::error("An error has occurred in the installation process");
    }

    public function onMakeAjentiReload(){

        $project = new Install;
        $project->makeAjentiReload();
    }

    public function onMakeDelete(){

        $project = Project::find(Request::input('Project.id'));
        
        if($project){

            $install = new Install;
            if($install->delete())
                Flash::success("The project has been deleted successfully");
        }else Flash::error("Error 404 Project not exists"); 
    }

    public function update($recordId = null, $context = null)
    {
        $this->vars['slug_domain'] = OctoSettings::get('slug_domain');

        $this->asExtension('FormController')->update($recordId, $context);
    }
    
    public function password($recordId = null){

        return $recordId;
    }
}