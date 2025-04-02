<?php

namespace App\Models\Dashboard_And_Reports;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class District extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, SoftDeletes;
    protected $fillable = ['name', 'name1', 'name2', 'area_id', 'area_name'];
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
