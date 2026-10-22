<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Taggable extends Pivot
{
    use AsPivot;
    protected $table = 'taggables';
    protected $fillable = [
        'tag_id',
        'taggable_type',
        'taggable_id',
    ];
}
