<?php

namespace App\Http\Controllers\Dashboard_And_Reports;

use App\Events\UploadFileRequestReceived;
use App\Http\Controllers\Controller;
use App\Http\Resources\fileManagerResource;
use App\Jobs\ProcessFileJob;
use App\Jobs\ProcessFileJob1;
use App\Jobs\ProcessFileJob2;
use App\Models\Content\BoNNGDTT;
use App\Models\Content\CDBR;
use App\Models\Content\GDTT;
use App\Models\Content\PAKH;
use App\Models\Content\SCTD;
use App\Models\Content\TramUuTienGDTT;
use App\Models\Content\WOTT;
use App\Models\Dashboard_And_Reports\District;
use App\Models\Dashboard_And_Reports\FileManager;

use App\Models\Dashboard_And_Reports\QLT;
use App\Models\User;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;

use Illuminate\Support\Facades\Concurrency;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PDOStatement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\SimpleExcel\SimpleExcelReader;
class FileManagerController extends Controller
{
    protected float $benchmarkStartTime;

    protected int $benchmarkStartMemory;

    protected int $startRowCount;

    protected int $startQueries;



    protected function startBenchmark(string $table = 'w_o_t_t_s'): void
    {
        $this->startRowCount = DB::table($table)->count();
        $this->benchmarkStartTime = microtime(true);
        $this->benchmarkStartMemory = memory_get_usage();
        DB::enableQueryLog();
        $this->startQueries = DB::select("SHOW SESSION STATUS LIKE 'Questions'")[0]->Value;
    }

    protected function endBenchmark(string $table = 'w_o_t_t_s'): void
    {
        $executionTime = microtime(true) - $this->benchmarkStartTime;
        $memoryUsage = round((memory_get_usage() - $this->benchmarkStartMemory) / 1024 / 1024, 2);
        $queriesCount = DB::select("SHOW SESSION STATUS LIKE 'Questions'")[0]->Value - $this->startQueries - 1; // Subtract the Questions query itself

        // Get row count after we've stopped tracking queries
        $rowDiff = DB::table($table)->count() - $this->startRowCount;

        $formattedTime = match (true) {
            $executionTime >= 60 => sprintf('%dm %ds', floor($executionTime / 60), $executionTime % 60),
            $executionTime >= 1 => round($executionTime, 2) . 's',
            default => round($executionTime * 1000) . 'ms',
        };
        dump("TIME: " . $formattedTime);
        dump("MEM: " . $memoryUsage);
        dump("SQL: " . $queriesCount);
        dump("ROWS: " . $rowDiff);
        // Log::info("TIME: " . $formattedTime);
        // Log::info("MEM: " . $memoryUsage);
        // Log::info("SQL: " . $queriesCount);
        // Log::info("ROWS: " . $rowDiff);

    }


    public function index(Request $request)
    {
        // dd(Str::slug('Thời điểm bắt đầu thực hiện (dd/MM/yyyy HH:mm:ss)', '_'));
        // dd(str::after('TTKT Hồ Chí Minh_Đội Kỹ thuật Quận 12', 'TTKT Hồ Chí Minh_Đội Kỹ thuật '));
        // dd($request->toArray());

        // $kt = Carbon::parse('2025-03-14 09:05:00');
        // $dong = Carbon::parse('2025-03-14 00:05:15');
        // $diffInHours = $kt->diffInHours($dong, false);
        // dd($diffInHours);
        // dd(ceil(abs($diffInHours / 24)));
        // $result = District::with('area')->get()->mapWithKeys(fn($district) => [
        //     $district->name => $district->area?->name,
        // ])->all();
        // dd($result);


        $query = FileManager::query();

        $filteredDataRequest = array_diff_key($request->toArray(), ["page" => "", 'per_page' => '']);

        $perPage = $request->input('per_page', 10);
        $data = $query->orderByDesc('id')->paginate($perPage);
        $data->getCollection()->transform(function ($role) {
            $role->count = $this->model($role->name, $role->uuid);
            return $role;
        });
        // dd($data);
        // dd(fileManagerResource::collection($data));
        return Inertia::render('dashboard_and_reports/page_filemanager', [
            'data' => fileManagerResource::collection($data),
        ]);
    }


    public function store(Request $request)
    {
        // $this->startBenchmark();
        $now = now()->format('Y-m-d H:i:s');
        $keywords = ['tramuutiengdtt', 'bonngdtt', 'gdtt', 'sctd', 'cdbr', 'wott', 'pakh', 'qlt',];
        $jobs = [];
        $userID = auth()->id();

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $files = $request->file('files');
        foreach ($files as $file) {
            $originalFilename = basename($file->getClientOriginalName());
            $chunkIndex = $request->input('chunkIndex');
            $totalChunks = $request->input('totalChunks');
            $fileNameFromClient = $request->input('fileName', $originalFilename);
            $idFile = "userID.{$userID}_{$fileNameFromClient}";
            $finalPath = "{$tempDir}/{$idFile}";
            // dd($finalPath, $idFile);


            if (!isset($chunkIndex)) {
                $file->move($tempDir, $idFile);
            } else {
                $tempPath = "{$tempDir}/{$idFile}.part{$chunkIndex}";
                file_put_contents($tempPath, $file->getContent(), FILE_APPEND);

                if ($chunkIndex == $totalChunks - 1) {
                    $out = fopen($finalPath, 'wb');
                    for ($i = 0; $i < $totalChunks; $i++) {
                        $chunkPath = "{$tempDir}/{$idFile}.part{$i}";
                        if (file_exists($chunkPath)) {
                            $in = fopen($chunkPath, 'rb');
                            stream_copy_to_stream($in, $out);
                            fclose($in);
                            unlink($chunkPath);
                        }
                    }
                    fclose($out);
                } else {
                    return response()->json(['message' => "Chunk {$chunkIndex}/{$totalChunks} uploaded"]);
                }
            }

            $problem = null;
            foreach ($keywords as $k) {
                if (stripos($fileNameFromClient, $k) !== false) {
                    $problem = $k;
                    break;
                }
            }





            $jobs[] = new ProcessFileJob($userID, $now, $problem, $finalPath, $fileNameFromClient);
        }

        Bus::batch($jobs)
            ->onQueue('high')
            ->then(fn() => Log::info('⚡ Batch started'))
            ->catch(fn() => Log::error('❌ Batch failed'))
            // ->finally(fn() => [Log::info('✅ Batch completed'), $this->endBenchmark()])
            ->finally(fn() => [Log::info('✅ Batch completed')])
            ->dispatch();

        return response()->json(['message' => 'Files uploaded and processing started']);
    }

    private function model($name, $uuid)
    {
        $keywords = [
            'gdtt' => GDTT::class,
            'sctd' => SCTD::class,
            'cdbr' => CDBR::class,
            'wott' => WOTT::class,
            'pakh' => PAKH::class,
            'qlt' => QLT::class,
            'bonngdtt' => BoNNGDTT::class,
            'tramuutiengdtt' => TramUuTienGDTT::class,
        ];
        $model = Arr::first(array_keys($keywords), fn($k) => Str::contains($name, $k));
        return $keywords[$model]::where('packed', $uuid)->count();
    }
}
