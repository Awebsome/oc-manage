<?php namespace AWME\OctoManage\Models;

use Model;

/**
 * Project Model
 */
class Project extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'awme_octomanage_projects';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['*'];

    protected $jsonable = ['plugins'];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    use \October\Rain\Database\Traits\Validation;

    public $rules = [
        'name'          => 'required|unique:awme_octomanage_projects,name|between:4,45|regex:/^[\w ]+$/',
        //'company'       => 'required|between:4,45',
        'slug_domain'       => 'required|unique:awme_octomanage_projects,slug_domain|between:4,255',
        'domains'       => 'required|between:4,255',
        'theme'         => 'required',
        'git'           => 'required',
        'host'          => 'required|between:4,16',
        'username'      => 'required|unique:awme_octomanage_projects,username|between:4,16|alpha_dash',
        'database'      => 'required|unique:awme_octomanage_projects,database|between:4,16|alpha_dash',
        'password'      => 'required|between:6,16',
        'ftphost'       => 'required|between:4,16',
        'ftpuser'       => 'required|unique:awme_octomanage_projects,ftpuser|between:4,16|alpha_dash',
        'ftppsw'        => 'required|between:4,16',
        'directory'     => 'required|unique:awme_octomanage_projects,directory|between:4,16|alpha_dash',
    ];
}