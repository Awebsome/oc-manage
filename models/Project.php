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

        #Installation
        'name'          => 'required|unique:awme_octomanage_projects,name|between:4,45|regex:/^[\w ]+$/',
        //'company'       => 'required|between:4,45',
        'slug_domain'       => 'required|unique:awme_octomanage_projects,slug_domain|between:4,120',
        'domains'       => 'required|between:4,255',
        'theme'         => 'required',
        'git'           => 'required',

        #Database Configs
        'db_username'      => 'required|unique:awme_octomanage_projects,db_username|between:4,16|alpha_dash',
        'db_password'      => 'required|between:6,16',
        'database'      => 'required|unique:awme_octomanage_projects,database|between:4,32|alpha_dash',

        #FTP Configs
        'ftp_login'       => 'required|unique:awme_octomanage_projects,ftp_login|between:4,16|alpha_dash',
        'ftp_password'        => 'required|between:4,16',
        'directory'     => 'required|unique:awme_octomanage_projects,directory|between:4,32|alpha_dash',
    ];
}