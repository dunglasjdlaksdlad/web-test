<?php

namespace App\Jobs;

use App\Events\UploadFileRequestReceived;
use App\Models\Content\CDBR;
use App\Models\Content\GDTT;
use App\Models\Content\PAKH;
use App\Models\Content\SCTD;
use App\Models\Content\WOTT;
use App\Models\Dashboard_And_Reports\District;
use App\Models\Dashboard_And_Reports\FileManager;
use DateTime;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Concurrency;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PDOStatement;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Expr\NullsafeMethodCall;
use Spatie\SimpleExcel\SimpleExcelReader;
class ProcessFileJob implements ShouldQueue
{
    use Queueable, Batchable
    ;

    /**
     * Create a new job instance.
     */
    public function __construct(private $userID, private $now, private $problem, private $filepath, private $filename)
    {
        $this->userID = $userID;
        $this->filepath = $filepath;
        $this->filename = $filename;
        $this->problem = $problem;
        $this->now = $now;
    }
    // public function handle(): void
    // {
    //     $packed = Str::uuid()->toString();
    //     $handle = fopen($this->filepath, 'r');
    //     $problem = $this->problem;
    //     // fgetcsv($handle);
    //     $header = array_map(fn($item) => Str::slug($item, '_'), fgetcsv($handle));
    //     // dd($header);
    //     fgetcsv($handle);

    //     $chunkSize = 500;
    //     $chunks = [];

    //     try {
    //         $stmt = $this->prepareChunkedStatement($chunkSize, $problem);

    //         while (($row = fgetcsv($handle)) !== false) {

    //             $data = array_combine($header, $row);
    //             $chunks = array_merge($chunks, $this->getChunkData($problem, $data, $packed, $this->now));

    //             if (count($chunks) >= $chunkSize * count($this->getColumns($problem))) {

    //                 $stmt->execute($chunks);
    //                 $chunks = [];
    //             }
    //         }

    //         if (!empty($chunks)) {
    //             $stmt = $this->prepareChunkedStatement(count($chunks) / count($this->getColumns($problem)), $problem);

    //             $stmt->execute($chunks);
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('File upload error: ' . $e->getMessage());
    //         // dd($chunks);
    //         // unlink($this->filepath);
    //         broadcast(new UploadFileRequestReceived(1, ['fileName' => $this->filename, 'status' => 'error']));
    //     } finally {
    //         fclose($handle);
    //         // unlink($this->filepath);
    //         FileManager::create(['name' => $this->filename, 'uuid' => $packed, 'created_by' => $this->userID, 'created_at' => $this->now]);

    //         broadcast(new UploadFileRequestReceived($this->userID, [
    //             'fileName' => $this->filename,
    //             'status' => 'success',
    //             'file' => [
    //                 'uuid' => $packed,
    //                 'fileName' => $this->filename,
    //                 // 'count' => $this->model($this->filename,$packed),
    //                 'created_by' => $this->userID,
    //                 'created_at' => $this->now,
    //             ]
    //         ]));
    //     }
    // }



    public function handle(): void
    {
        $packed = Str::uuid()->toString();
        $problem = $this->problem;
        $chunkSize = 500;
        $chunks = [];

        try {

            //     ->formatHeadersUsing(fn($header) => Str::slug($header, '_'));
            // if ($problem === 'wott') {
            // DB::statement('SET FOREIGN_KEY_CHECKS=0'); // Tắt kiểm tra khóa ngoại
            // }

            $stmt = $this->prepareChunkedStatement($chunkSize, $problem);

            if ($problem == 'qlt') {
                $reader = SimpleExcelReader::create($this->filepath);
            } else {
                $reader = SimpleExcelReader::create($this->filepath)->headerOnRow(7);
            }

            $reader->formatHeadersUsing(fn($header) => Str::slug($header, '_'))
                ->getRows()
                ->each(function ($row) use (&$chunks, $chunkSize, $stmt, $problem, $packed) {
                    // dump($row);
                    // dd($row);
                    $chunks = array_merge($chunks, $this->getChunkData($problem, $row, $packed, $this->now));

                    if (count($chunks) >= $chunkSize * count($this->getColumns($problem))) {
                        $stmt->execute($chunks);
                        $stmt->closeCursor();
                        $chunks = [];
                    }
                });

            if (!empty($chunks)) {
                $stmt = $this->prepareChunkedStatement(count($chunks) / count($this->getColumns($problem)), $problem);
                $stmt->execute($chunks);
            }
        } catch (\Exception $e) {
            Log::error('File upload error: ' . $e->getMessage());
            broadcast(new UploadFileRequestReceived(1, ['fileName' => $this->filename, 'status' => 'error']));
        } finally {
            FileManager::create(['name' => $this->filename, 'uuid' => $packed, 'created_by' => $this->userID, 'created_at' => $this->now]);
            broadcast(new UploadFileRequestReceived($this->userID, [
                'fileName' => $this->filename,
                'status' => 'success',
                'file' => [
                    'uuid' => $packed,
                    'fileName' => $this->filename,
                    'created_by' => $this->userID,
                    'created_at' => $this->now,
                ]
            ]));
            $unmatched = DB::table('w_o_t_t_s')
                ->leftJoin('q_l_t_s', 'w_o_t_t_s.ma_tram', '=', 'q_l_t_s.ma_tram')
                ->whereNotNull('w_o_t_t_s.ma_tram')
                ->whereNull('q_l_t_s.ma_tram')
                ->pluck('w_o_t_t_s.ma_tram')
                ->all();
            Log::info('Các ma_tram không khớp: ', $unmatched);
            unlink($this->filepath);
        }
    }
    private function getChunkData($problem, $data, $packed, $now)
    {
        return match ($problem) {
            'cdbr' => [
                implode(',', [$data['ma_su_co'], $data['dinh_danh_su_co'], $data['bo_nguyen_nhan']]),
                $data['ma_su_co'] ?? null,
                $data['dinh_danh_su_co'] ?? null,
                $data['khu_vuc'] ?? null,
                $data['quan'] ?? null,
                $data['ma_tram'] ?? null,
                $this->convertToDatetime($data['thoi_gian_bat_dau']),
                $this->convertToDatetime($data['thoi_gian_ket_thuc']),
                $data['tong_thoi_gian'] ?? null,
                $this->convertToDatetime($data['ngay_ps_sc']),
                null,
                $data['nn_muc_1'] ?? null,
                $packed,
                'active',
                $now,
                $now
            ],
            'gdtt' => [
                implode(',', [
                    $data['ma_tu_btsnodeb'],
                    $data['ten_cell'],
                    $data['thoi_gian_xuat_hien_canh_bao'],
                    $data['thoi_gian_ket_thuc'],
                    $data['ma_tram_ghep'],
                    $data['dem_so_su_co'],
                ]),
                $data['khu_vuc'] ?? null,
                $data['quanhuyen'] ?? null,
                $data['loai_tu'] ?? null,
                $data['ma_nha_tram_chuan'] ?? null,
                $data['ten_canh_bao'] ?? null,
                $this->convertToDatetime($data['thoi_gian_xuat_hien_canh_bao']),
                $this->convertToDatetime($data['thoi_gian_ket_thuc']),
                $data['thoi_gian_ton'] ?? null,
                $data['cellh_sau_giam_tru'] ?? null,
                $data['nn_muc_1'] ?? null,
                $data['giam_tru_muc_tinh'] . $data['dem_trung'] . $data['tram_small_cell'],
                $packed,
                'active',
                $now,
                $now
            ],
            'sctd' => [
                $data['ma_su_co'] ?? null,
                $data['ttkv'] ?? null,
                $data['huyen'] ?? null,
                $data['ma_su_co'] ?? null,
                // $this->convertToDatetime($data['thoi_diem_bat_dau']),
                // $this->convertToDatetime($data['thoi_diem_ket_thuc']),
                // $this->convertToDatetime($data['thoi_gian_anh_huong_dich_vuh']),
                // $this->convertToDatetime($data['thoi_gian_khac_phuc_loi']),
                $this->convertToDatetime($data['ngay_ps']),
                $data['phan_loai'] ?? null,
                $data['loai_nn_lop_1'] ?? null,
                $data['filter_data'] ?? null,
                $packed,
                'active',
                $now,
                $now
            ],
            'wott' => [
                $data['ma_cong_viec'] ?? null,
                $data['ma_cong_viec'] ?? null,
                $data['ma_tram'] ?? null,
                // $this->resolveMaTram($data['ma_tram'] ?? null),
                $data['trang_thai'] ?? null,
                Str::after($data['nhom_dieu_phoi'], 'TTKT Hồ Chí Minh_Đội Kỹ thuật ') ?? null,
                $this->convertToDatetime($data['thoi_diem_tao']),
                $this->convertToDatetime($data['thoi_diem_yeu_cau_ket_thuc_ddmmyyyy_hhmmss']),
                $this->convertToDatetime($data['thoi_diem_cd_dong']),
                $data['nhan_vien_thuc_hien'] ? $data['nhan_vien_thuc_hien'] : self::checkNV($data['nhom_dieu_phoi']),
                // self::checkNV($data['nhom_dieu_phoi']),
                null,
                $data['muc_do_uu_tien'] ?? null,
                $packed,
                $now,
                $now,
                null
            ],
            'pakh' => [
                $data['ma_cong_viec'] ?? null,
                $data['ma_cong_viec'] ?? null,
                $data['ma_tram'] ?? null,
                // $this->resolveMaTram($data['ma_tram'] ?? null),
                $data['trang_thai'] ?? null,
                $data['he_thong'] ?? null,
                // $data['ma_tram'] ? null : Str::after($data['nhom_dieu_phoi'], 'TTKT Hồ Chí Minh_Đội Kỹ thuật '),
                Str::after($data['nhom_dieu_phoi'], 'TTKT Hồ Chí Minh_Đội Kỹ thuật ') ?? null,
                $this->convertToDatetime($data['thoi_diem_tao']),
                $this->convertToDatetime($data['thoi_diem_yeu_cau_ket_thuc_ddmmyyyy_hhmmss']),
                $this->convertToDatetime($data['thoi_diem_cd_dong']),
                $data['nhan_vien_thuc_hien'] ? $data['nhan_vien_thuc_hien'] : self::checkNV($data['nhom_dieu_phoi']),
                null,
                $data['muc_do_uu_tien'] ?? null,
                $packed,
                $now,
                $now,
                null
            ],
            'qlt' => [
                trim($data['ma_tram']) ?? null,
                $data['ttkv'] ?? null,
                $data['quan_huyen'] ?? null,
                $data['ma_nhan_vien_thuc_te_ql'] ?? null,
                $data['user_vt'] ?? null,
                $data['sdt'] ?? null,
                $data['ten_nhan_vien_thuc_te_ql'] ?? null,
                $data['tham_nien_quan_ly_tram'] ?? null,
                $data['loai_tram1'] ?? null,
                $data['loai_tram'] ?? null,
                $packed,
                $now,
                $now,
                null
            ],
        };
    }
    private function getColumns($problem)
    {
        return match ($problem) {
            'cdbr' => ['uuid', 'ma_su_co', 'dinh_danh_su_co', 'khu_vuc', 'quan', 'ma_tram', 'thoi_gian_bat_dau', 'thoi_gian_ket_thuc', 'tong_thoi_gian', 'ngay_ps_sc', 'filter_data', 'nn_muc_1', 'packed', 'status', 'created_at', 'updated_at'],
            'gdtt' => ['uuid', 'khu_vuc', 'quanhuyen', 'loai_tu', 'ma_nha_tram_chuan', 'ten_canh_bao', 'thoi_gian_xuat_hien_canh_bao', 'thoi_gian_ket_thuc', 'thoi_gian_ton', 'cellh_sau_giam_tru', 'nn_muc_1', 'filter_data', 'packed', 'status', 'created_at', 'updated_at'],
            'sctd' => ['uuid', 'ttkv', 'huyen', 'ma_su_co', 'ngay_ps', 'phan_loai', 'loai_nn_lop_1', 'filter_data', 'packed', 'status', 'created_at', 'updated_at'],
            'wott' => [
                'uuid',
                'ma_cong_viec',
                'ma_tram',
                'trang_thai',
                'nhom_dieu_phoi',
                'thoi_diem_bat_dau',
                'thoi_diem_ket_thuc',
                'thoi_diem_cd_dong',
                'nhan_vien_thuc_hien',
                'danh_gia_wo_thuc_hien',
                'muc_do_uu_tien',
                'packed',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'pakh' => [
                'uuid',
                'ma_cong_viec',
                'ma_tram',
                'trang_thai',
                'he_thong',
                'nhom_dieu_phoi',
                'thoi_diem_bat_dau',
                'thoi_diem_ket_thuc',
                'thoi_diem_cd_dong',
                'nhan_vien_thuc_hien',
                'danh_gia_wo_thuc_hien',
                'muc_do_uu_tien',
                'packed',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'qlt' => [
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
                'created_at',
                'updated_at',
                'deleted_at',
            ]
        };
    }

    private function prepareChunkedStatement($chunkSize, $problem): PDOStatement
    {
        $columns = $this->getColumns($problem);
        $placeholders = implode(',', array_fill(0, $chunkSize, '(' . implode(', ', array_fill(0, count($columns), '?')) . ')'));
        $table = match ($problem) {
            'cdbr' => 'c_d_b_r_s',
            'gdtt' => 'g_d_t_t_s',
            'sctd' => 's_c_t_d_s',
            'wott' => 'w_o_t_t_s',
            'pakh' => 'p_a_k_h_s',
            'qlt' => 'q_l_t_s',
        };
        $updateColumns = array_filter($columns, fn($col) => $col !== 'created_at' && $col !== 'uuid');
        $updateQuery = implode(', ', array_map(fn($col) => "$col = VALUES($col)", $updateColumns));
        // dd($columns, $updateQuery);
        return DB::connection()->getPdo()->prepare("
            INSERT INTO {$table} (" . implode(',', $columns) . ")
            VALUES {$placeholders}
            ON DUPLICATE KEY UPDATE {$updateQuery}
        ");
    }

    private function convertToDatetime(?string $timeString): ?string
    {
        if (!$timeString = trim($timeString)) {
            return null;
        }

        // if (is_numeric($timeString)) {
        //     try {
        //         return Carbon::instance(Date::excelToDateTimeObject($timeString))->format('Y-m-d H:i:s');
        //     } catch (\Exception $e) {
        //         return null;
        //     }
        // }

        $formats = ['d/m/Y H:i:s', 'd/m/Y H:i', 'Y/m/d H:i', 'Y/m/d H:i:s', 'm/d/Y h:i', 'm/d/Y h:i:s A'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($timeString))->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    private function resolveMaTram(?string $maTram): ?string
    {
        $maTram = trim($maTram);
        if (!$maTram || !DB::table('q_l_t_s')->where('ma_tram', $maTram)->exists()) {
            if ($maTram) {
                Log::warning("ma_tram '$maTram' không tồn tại trong q_l_t_s, gán thành NULL.");
            }
            return null;
        }
        return $maTram;
    }

    private function checkNV(?string $quan): ?string
    {
        $quan = trim($quan);
        // dump($quan);
        return District::with('area')
            ->where('name2', Str::after($quan, 'TTKT Hồ Chí Minh_Đội Kỹ thuật '))
            ->first()?->area?->nv_gan;
    }
    private function checkNV1(string $nv, string $quan): string
    {
        if (!empty($nv)) {
            return $nv;
        } else {

            $quan = trim($quan);
            dump($quan);
            return District::with('area')
                ->where('name2', Str::after($quan, 'TTKT Hồ Chí Minh_Đội Kỹ thuật '))
                ->first()?->area?->nv_gan;
        }
    }

}
