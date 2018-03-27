<?php

namespace esc\Models;


use Dedi;
use Illuminate\Database\Eloquent\Model;
use Karma;
use LocalRecord;

class Map extends Model
{
    protected $table = 'maps';

    protected $fillable = ['UId', 'MxId', 'Name', 'FileName', 'Plays', 'Author', 'Mood', 'LapRace', 'LastPlayed', 'Environment', 'NbLaps', 'NbCheckpoints'];

    public $timestamps = false;

    public function locals()
    {
        return $this->hasMany(LocalRecord::class, 'Map', 'id');
    }

    public function dedis()
    {
        return $this->hasMany(Dedi::class, 'Map');
    }

    public function author()
    {
        return $this->hasOne(Player::class, 'Login', 'Author');
    }

    public function ratings()
    {
        return $this->hasMany(Karma::class, 'Map', 'id');
    }
}