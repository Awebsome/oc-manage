<?php namespace AWME\OctoManage\Models;

use Model;

/**
 * OctoSettings Model
 */
class OctoSettings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'octosettings';
    public $settingsFields = 'fields.yaml';
    
    /**
     * @var string The database table used by the model.
     */
    public $table = 'awme_octomanage_settings';

    use \October\Rain\Database\Traits\Validation;

    public $rules = [
        #OctoManage Configs
        'octo_label'	=> 'required',
        'slug_domain'   => 'required',
        'projects_path' => 'required',

        #Project Admin Acccount Configs
        'backend_first_name' => 'required|between:4,16',
        'backend_last_name' => 'required|between:4,16',
        'backend_email' => 'required|email|between:4,64',

        'backend_login' => 'required|between:4,16|alpha_dash',
        'backend_password'  => 'required|between:4,16',
    ];
}