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

}