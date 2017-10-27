<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class File
 * @package App
 */
class File extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'user_key', 'name', 'file', 'type' ,'size' , 'code', 'public'
    ];

}

