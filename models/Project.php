<?php namespace AWME\OctoManage\Models;

use Model;

/**
 * Project Model
 */
class Project extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $rules = [
        'name'          => 'required|between:4,45|alpha_dash',
        'company'       => 'required|between:4,45',
        'domains'       => 'required|between:4,255',
        'theme'         => 'required',
        'git'           => 'required',
        'host'          => 'required|between:4,16',
        'username'      => 'required|between:4,16|alpha_dash',
        'database'      => 'required|between:4,16|alpha_dash',
        'password'      => 'required|between:6,16',
        'ftphost'       => 'required|between:4,16',
        'ftpuser'       => 'required|between:4,16|alpha_dash',
        'ftppsw'        => 'required|between:4,16',
        'directory'     => 'required|between:4,16|alpha_dash',
    ];
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

}