<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TramUuTienGDTT extends Model
{
    use SoftDeletes;
    protected $fill = [
        'uuid',
        'ma_nha_tram_chuan',
        'ma_bts',
        'cau_hinh',
        'packed',
    ];
}
