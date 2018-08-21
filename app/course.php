<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class course extends Model
{
    public function user()
    {
        return $this->belongsTo('App\user');
    }

    public  function chapter()
    {
        return $this->hasMany('App\chapter')->orderBy('id');
    }

    public function enrollment(){
        return $this->hasMany('App\enrollment');
    }
    protected $fillable = [
        'name','description','cost'
    ];
}
