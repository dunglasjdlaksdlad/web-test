<?php

namespace App\Http\Controllers\Dashboard_And_Reports;

use App\Events\UploadFileRequestReceived;
use App\Http\Controllers\Controller;
use App\Http\Resources\fileManagerResource;
use App\Jobs\ProcessFileJob;
use App\Jobs\ProcessFileJob1;
use App\Jobs\ProcessFileJob2;
use App\Models\Content\CDBR;
use App\Models\Content\GDTT;
use App\Models\Content\PAKH;
use App\Models\Content\SCTD;
use App\Models\Content\WOTT;
use App\Models\Dashboard_And_Reports\FileManager;

use App\Models\User;
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
class FileManagerController_test extends Controller
{
    protected float $benchmarkStartTime;

    protected int $benchmarkStartMemory;

    protected int $startRowCount;

    protected int $startQueries;



    protected function startBenchmark(string $table = 'c_d_b_r_s'): void
    {
        $this->startRowCount = DB::table($table)->count();
        $this->benchmarkStartTime = microtime(true);
        $this->benchmarkStartMemory = memory_get_usage();
        DB::enableQueryLog();
        $this->startQueries = DB::select("SHOW SESSION STATUS LIKE 'Questions'")[0]->Value;
    }

    protected function endBenchmark(string $table = 'c_d_b_r_s'): void
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
        // dump($formattedTime);
        // dump($memoryUsage);
        // dump($queriesCount);
        // dump($rowDiff);
        Log::info("TIME: " . $formattedTime);
        Log::info("MEM: " . $memoryUsage);
        Log::info("SQL: " . $queriesCount);
        Log::info("ROWS: " . $rowDiff);

    }


    public function index(Request $request)
    {
        // dd($request->toArray());
        $query = FileManager::query();

        $filteredDataRequest = array_diff_key($request->toArray(), ["page" => "", 'per_page' => '']);

        // if ($filteredDataRequest) {
        //     foreach ($filteredDataRequest as $key => $value) {
        //         $query->where($key, 'like', '%' . $value . '%');
        //     }
        // }

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

    private function model($name, $uuid)
    {
        $keywords = [
            'gdtt' => GDTT::class,
            'sctd' => SCTD::class,
            'cdbr' => CDBR::class,
            'wott' => WOTT::class,
            'pakh' => PAKH::class
        ];
        $model = Arr::first(array_keys($keywords), fn($k) => Str::contains($name, $k));
        return $keywords[$model]::where('packed', $uuid)->count();
    }



    // public function store(Request $request)
    // {
    //     $this->startBenchmark();
    //     $now = now()->format('Y-m-d H:i:s');
    //     $keywords = ['gdtt', 'sctd', 'cdbr', 'wott', 'pakh1'];
    //     $jobs = [];
    //     $userID = auth()->id();

    //     $tempDir = storage_path('app/temp');
    //     if (!is_dir($tempDir)) {
    //         mkdir($tempDir, 0755, true);
    //     }

    //     $files = $request->file('files');
    //     foreach ($files as $file) {
    //         $originalFilename = basename($file->getClientOriginalName());
    //         $chunkIndex = $request->input('chunkIndex');
    //         $totalChunks = $request->input('totalChunks');
    //         $fileNameFromClient = $request->input('fileName', $originalFilename);
    //         $idFile = "userID.{$userID}_{$fileNameFromClient}";
    //         $finalPath = "{$tempDir}/{$idFile}";


    //         if (!isset($chunkIndex)) {
    //             $file->move($tempDir, $idFile);
    //         } else {
    //             $tempPath = "{$tempDir}/{$idFile}.part{$chunkIndex}";
    //             file_put_contents($tempPath, $file->getContent(), FILE_APPEND);

    //             if ($chunkIndex == $totalChunks - 1) {
    //                 $out = fopen($finalPath, 'wb');
    //                 for ($i = 0; $i < $totalChunks; $i++) {
    //                     $chunkPath = "{$tempDir}/{$idFile}.part{$i}";
    //                     if (file_exists($chunkPath)) {
    //                         $in = fopen($chunkPath, 'rb');
    //                         stream_copy_to_stream($in, $out);
    //                         fclose($in);
    //                         unlink($chunkPath);
    //                     }
    //                 }
    //                 fclose($out);
    //             } else {
    //                 return response()->json(['message' => "Chunk {$chunkIndex}/{$totalChunks} uploaded"]);
    //             }
    //         }

    //         $problem = null;
    //         foreach ($keywords as $k) {
    //             if (stripos($fileNameFromClient, $k) !== false) {
    //                 $problem = $k;
    //                 break;
    //             }
    //         }

    //         $jobs[] = new ProcessFileJob($userID, $now, $problem, $finalPath, $fileNameFromClient);
    //     }

    //     Bus::batch($jobs)
    //         ->onQueue('high') // Dùng queue ưu tiên cao
    //         ->then(fn() => Log::info('⚡ Batch started'))
    //         ->catch(fn() => Log::error('❌ Batch failed'))
    //         ->finally(fn() => [Log::info('✅ Batch completed'), $this->endBenchmark()])
    //         ->dispatch();

    //     return response()->json(['message' => 'Files uploaded and processing started']);
    // }




    public function store(Request $request)
    {

        $now = now()->format('Y-m-d H:i:s');
        $keywords = ['gdtt', 'sctd', 'cdbr', 'wott', 'pakh1'];
        $jobs = [];
        $userID = auth()->id();
        $numberOfProcesses = 10;
        $tasks = [];

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $files = $request->file('files');
        foreach ($files as $file) {
            $originalFilename = basename($file->getClientOriginalName());
            $chunkIndex = $request->input('chunkIndex');
            $totalChunks = $request->input('totalChunks');
            // dd($chunkIndex,$totalChunks);
            $fileNameFromClient = $request->input('fileName', $originalFilename);
            $idFile = "userID.{$userID}_{$fileNameFromClient}";
            $finalPath = "{$tempDir}/{$idFile}";


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

            /////////////////////////////////////////////////////////////////
            $this->startBenchmark();

            // $reader = SimpleExcelReader::create($finalPath)
            //     ->formatHeadersUsing(fn($header) => Str::slug($header, '_'));

            // foreach ($reader->getRows() as $row) {
            //     // Xử lý từng dòng
            //     if ($row['stt'] == 123) {

            //         dd($row);
            //     }
            //     // Log::info("data: " . $row);
            // }

            for ($i = 0; $i < $numberOfProcesses; $i++) {
                $tasks[] = function () use ($finalPath, $i, $numberOfProcesses, $now) {
                    DB::reconnect();

                    $handle = fopen($finalPath, 'r');
                    // fgets($handle); // Skip header
                    $header = array_map(fn($item) => Str::slug($item, '_'), fgetcsv($handle));
                    $currentLine = 0;
                    $customers = [];

                    while (($line = fgets($handle)) !== false) {
                        // Each process takes every Nth line
                        if ($currentLine++ % $numberOfProcesses !== $i) {
                            continue;
                        }

                        $row = str_getcsv($line);
                        $data = array_combine($header, $row);
                        $customers[] = [
                            'uuid' => implode(',', [$data['ma_su_co'], $data['dinh_danh_su_co'], $data['bo_nguyen_nhan']]),
                            'ma_su_co' => null,
                            'dinh_danh_su_co' => null,
                            'khu_vuc' => $data['khu_vuc'],
                            'quan' => null,
                            'ma_tram' => null,
                            'thoi_gian_bat_dau' => null,
                            'thoi_gian_ket_thuc' => null,
                            'tong_thoi_gian' => null,
                            'ngay_ps_sc' => null,
                            'filter_data' => null,
                            'nn_muc_1' => null,
                            'packed' => null,
                            'status' => null,
                            'created_at' => $now,
                            'updated_at' => $now
                        ];

                        // $customers[] = [
                        //     'uuid' => ,
                        //     'ma_cong_viec' => ,
                        //     'ttkv' => ,
                        //     'quan' => ,
                        //     'thoi_diem_ket_thuc' => ,
                        //     'wo_qua_han' => ,
                        //     'loai_wo' => ,
                        //     'packed' => ,
                        //     'status' => ,
                        //     'created_at' => ,
                        //     'updated_at' => ,
                        //     'deleted_at' => ,
                        // ];


                        if (count($customers) === 1000) {
                            self::upsertWithRetry($customers);
                            $customers = [];
                        }
                    }

                    if (!empty($customers)) {
                        self::upsertWithRetry($customers);
                    }

                    fclose($handle);

                    return true;
                };
            }

        }
        Concurrency::run($tasks);
        $this->endBenchmark();
    }




    // public function store(Request $request)
    // {

    //     $now = now()->format('Y-m-d H:i:s');
    //     $keywords = ['gdtt', 'sctd', 'cdbr', 'wott', 'pakh1'];
    //     $jobs = [];
    //     $userID = auth()->id();
    //     $numberOfProcesses = 10;
    //     $tasks = [];

    //     $tempDir = storage_path('app/temp');
    //     if (!is_dir($tempDir)) {
    //         mkdir($tempDir, 0755, true);
    //     }

    //     $files = $request->file('files');
    //     foreach ($files as $file) {
    //         $originalFilename = basename($file->getClientOriginalName());
    //         $chunkIndex = $request->input('chunkIndex');
    //         $totalChunks = $request->input('totalChunks');
    //         // dd($chunkIndex,$totalChunks);
    //         $fileNameFromClient = $request->input('fileName', $originalFilename);
    //         // dd($fileNameFromClient);
    //         $idFile = "userID.{$userID}_{$fileNameFromClient}";
    //         $finalPath = "{$tempDir}/{$idFile}";


    //         if (!isset($chunkIndex)) {
    //             $file->move($tempDir, $idFile);
    //         } else {
    //             $tempPath = "{$tempDir}/{$idFile}.part{$chunkIndex}";
    //             file_put_contents($tempPath, $file->getContent(), FILE_APPEND);

    //             if ($chunkIndex == $totalChunks - 1) {
    //                 $out = fopen($finalPath, 'wb');
    //                 for ($i = 0; $i < $totalChunks; $i++) {
    //                     $chunkPath = "{$tempDir}/{$idFile}.part{$i}";
    //                     if (file_exists($chunkPath)) {
    //                         $in = fopen($chunkPath, 'rb');
    //                         stream_copy_to_stream($in, $out);
    //                         fclose($in);
    //                         unlink($chunkPath);
    //                     }
    //                 }
    //                 fclose($out);
    //             } else {
    //                 return response()->json(['message' => "Chunk {$chunkIndex}/{$totalChunks} uploaded"]);
    //             }
    //         }

    //         $problem = null;
    //         foreach ($keywords as $k) {
    //             if (stripos($fileNameFromClient, $k) !== false) {
    //                 $problem = $k;
    //                 break;
    //             }
    //         }

    //         /////////////////////////////////////////////////////////////////
    //         $this->startBenchmark();

    //         for ($i = 0; $i < $numberOfProcesses; $i++) {
    //             $tasks[] = function () use ($finalPath, $i, $numberOfProcesses, $now) {
    //                 DB::reconnect();

    //                 $reader = SimpleExcelReader::create($finalPath)
    //                     ->formatHeadersUsing(fn($header) => Str::slug($header, '_'));
    //                 $currentLine = 0;
    //                 $customers = [];


    //                 foreach ($reader->getRows() as $row) {
    //                     if ($currentLine++ % $numberOfProcesses !== $i) {
    //                         continue;
    //                     }

    //                     $customers[] = [
    //                         'uuid' => implode(',', [$row['ma_su_co'], $row['dinh_danh_su_co'], $row['bo_nguyen_nhan']]),
    //                         'ma_su_co' => null,
    //                         'dinh_danh_su_co' => null,
    //                         'khu_vuc' => $row['khu_vuc'],
    //                         'quan' => null,
    //                         'ma_tram' => null,
    //                         'thoi_gian_bat_dau' => null,
    //                         'thoi_gian_ket_thuc' => null,
    //                         'tong_thoi_gian' => null,
    //                         'ngay_ps_sc' => null,
    //                         'filter_data' => null,
    //                         'nn_muc_1' => null,
    //                         'packed' => null,
    //                         'status' => null,
    //                         'created_at' => $now,
    //                         'updated_at' => $now
    //                     ];

    //                     // $customers[] = [
    //                     //     'uuid' => $row['ma_cong_viec'],
    //                     //     'ma_cong_viec' => $row['stt'],
    //                     //     'ttkv' => null,
    //                     //     'quan' => null,
    //                     //     'thoi_diem_ket_thuc' => null,
    //                     //     'wo_qua_han' => null,
    //                     //     'loai_wo' => null,
    //                     //     'packed' => null,
    //                     //     'status' => null,
    //                     //     'created_at' => $now,
    //                     //     'updated_at' => $now,
    //                     //     'deleted_at' => null,
    //                     // ];

    //                     if (count($customers) === 1000) {
    //                         self::upsertWithRetry($customers);
    //                         $customers = [];
    //                     }
    //                 }

    //                 if (!empty($customers)) {
    //                     self::upsertWithRetry($customers);
    //                 }
    //                 $reader->close();
    //                 return true;
    //             };
    //         }

    //     }
    //     Concurrency::run($tasks);
    //     $this->endBenchmark();
    // }
    private static function upsertWithRetry(array $customers): void
    {
        $maxAttempts = 5;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                DB::transaction(function () use ($customers) {
                    DB::table('c_d_b_r_s')->upsert(
                        $customers,
                        ['uuid'],
                        ['khu_vuc', 'updated_at']
                    );
                });
                break;
            } catch (\Exception $e) {
                $attempt++;
                if ($attempt === $maxAttempts) {
                    \Log::error("Upsert failed after $maxAttempts attempts: " . $e->getMessage());
                    break;
                }
                DB::reconnect();
                usleep(100000);
            }
        }
    }
}
