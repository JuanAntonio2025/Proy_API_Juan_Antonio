<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Petition extends Model
{
    use HasFactory;

    protected $table = 'petitions';

    protected $fillable = [
        'title',
        'description',
        'addressee',
        'signatories',
        'status',
        'user_id',
        'category_id',
    ];

    protected $hidden = ['user_id', 'category_id'];

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function userSigners() {
        return $this->belongsToMany(User::class, 'petition_user', 'petition_id', 'user_id');
    }

    public function files() {
        return $this->hasMany(File::class);
    }
}
