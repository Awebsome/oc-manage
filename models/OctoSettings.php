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
        'backend_username'	=> 'required|between:4,16|alpha_dash',
        'backend_password'  => 'required|between:4,16',
        'slug_domain'	=> 'required',

        'projects_path'	=> 'required',
        'octo_label'	=> 'required',
    ];
}