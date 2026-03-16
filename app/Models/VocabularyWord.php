<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabularyWord extends Model
{
    protected $fillable = [
        'vocabulary_list_id', 'turkish_word', 'uzbek_translation',
        'example_sentence', 'difficulty_level', 'audio_path', 'order_index',
    ];

    public function list() { return $this->belongsTo(VocabularyList::class, 'vocabulary_list_id'); }
}
