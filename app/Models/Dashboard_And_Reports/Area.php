<?php

namespace App\Models\Dashboard_And_Reports;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name','nv_gan', 'created_by', 'updated_by'];
    public function districts()
    {

        return $this->hasMany(District::class);
    }
}
