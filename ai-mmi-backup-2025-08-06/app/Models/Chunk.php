<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chunk extends Model
{
    protected $table = 'app_chunks';
    protected $fillable = [
        'source_type','source_id','chunk_index','content','meta'
    ];
    protected $casts = ['meta' => 'array'];
}
