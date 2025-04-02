<?php

namespace App\Models\Dashboard_And_Reports;

use App\Models\Content\WOTT1;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QLT extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ma_tram',
        'ttkv',
        'quan',
        'ma_nhan_vien_thuc_te_ql',
        'user_vt',
        'sdt',
        'ten_nhan_vien_thuc_te_ql',
        'tham_nien_quan_ly_tram',
        'loai_tram1',
        'loai_tram',
        'packed',
    ];


}
