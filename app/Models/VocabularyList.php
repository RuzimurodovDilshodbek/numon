<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabularyList extends Model
{
    protected $fillable = ['title', 'group_id', 'description', 'created_by'];

    public function words() { return $this->hasMany(VocabularyWord::class)->orderBy('order_index'); }
    public function group() { return $this->belongsTo(Group::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
