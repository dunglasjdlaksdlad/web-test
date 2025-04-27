<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoNNGDTT extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'uuid',
        'dau_vao',
        'muc_1',
        'giam_tru_muc_kv',
        'giam_tru_muc_tinh',
        'packed',
    ];
}
