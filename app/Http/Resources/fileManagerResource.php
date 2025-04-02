<?php

namespace App\Http\Resources;

use App\Models\Content\CDBR;
use App\Models\Content\GDTT;
use App\Models\Content\PAKH;
use App\Models\Content\SCTD;
use App\Models\Content\WOTT;
use App\Models\Dashboard_And_Reports\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class fileManagerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            // 'id' => (string) $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'count' => $this->count,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    private function model($name,$uuid) {
        // $keywords = ['gdtt', 'sctd', 'cdbr', 'wott', 'pakh'];
        
        $keywords = [
            'gdtt' => GDTT::class,
            'sctd' => SCTD::class,
            'cdbr' => CDBR::class,
            'wott' => WOTT::class,
            'pakh' => PAKH::class
        ];
        $model = Arr::first(array_keys($keywords), fn($k) => Str::contains($name, $k));
        // dd($model,$keywords[$model]);

       return $keywords[$model]::where('packed', $uuid)->count();
    }
}
