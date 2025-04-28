<?php

namespace App\Jobs;

use App\Events\UploadFileRequestReceived;
use App\Models\Dashboard_And_Reports\District;
use App\Models\Dashboard_And_Reports\FileManager;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDOStatement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\SimpleExcel\SimpleExcelReader;
class ProcessFileJob implements ShouldQueue
{
    use Queueable, Batchable;

    private array $districtCache = [];
    public function __construct(private $userID, private $now, private $problem, private $filepath, private $filename)
    {
        $this->userID = $userID;
        $this->filepath = $filepath;
        $this->filename = $filename;
        $this->problem = $problem;
        $this->now = $now;
        $this->loadDistrictCache($problem);

    }
    public function handle(): void
    {
        $packed = Str::uuid()->toString();
        $problem = $this->problem;
        $chunkSize = 1000;
        $chunks = [];

        try {
            $stmt = $this->prepareChunkedStatement($chunkSize, $problem);
            $reader = in_array($problem, ['qlt', 'bonngdtt', 'tramuutiengdtt'])
                ? SimpleExcelReader::create($this->filepath)
                : (in_array($problem, ['gdtt'])
                    ? SimpleExcelReader::create($this->filepath)->headerOnRow(2)
                    : SimpleExcelReader::create($this->filepath)->headerOnRow(7));
            $reader->formatHeadersUsing(fn($header) => Str::slug($header, '_'))
                ->getRows()
                ->each(function ($row) use (&$chunks, $chunkSize, $stmt, $problem, $packed) {
                    // dump($row);
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
            unlink($this->filepath);
        }
    }
    private function getChunkData($problem, $data, $packed, $now)
    {
        // if ($problem == 'wott') {
        if (in_array($problem, ['wott', 'pakh'])) {
            $data['thoi_diem_tao'] = $this->convertToDatetime($data['thoi_diem_tao']);
            $data['thoi_diem_yeu_cau_ket_thuc_ddmmyyyy_hhmmss'] = $this->convertToDatetime($data['thoi_diem_yeu_cau_ket_thuc_ddmmyyyy_hhmmss']);
            $data['thoi_diem_cd_dong'] = $this->convertToDatetime($data['thoi_diem_cd_dong']);
            // $data['thoi_diem_ket_thuc'] = $this->convertToDatetime($data['thoi_diem_ket_thuc']);
        } elseif (in_array($problem, ['gdtt'])) {
            $data['thoi_gian_xuat_hien_canh_bao'] = $this->convertToDatetime($data['thoi_gian_xuat_hien_canh_bao']);
            $data['thoi_gian_ket_thuc'] = $this->convertToDatetime($data['thoi_gian_ket_thuc']);
            $metrics = $this->splitDayNightHours(Carbon::parse($data['thoi_gian_xuat_hien_canh_bao']), $data['thoi_gian_ton']);
        }
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
            // 'gdtt' => [
            //     $data['nhom_canh_bao'] == "Mất Luồng" ?
            //     implode(',', [
            //         $data['ma_tu_btsnodeb'],
            //         $data['thoi_gian_xuat_hien_canh_bao'],
            //         $data['thoi_gian_ket_thuc'],
            //     ]) : implode(',', [
            //             $data['ten_cell'],
            //             $data['thoi_gian_xuat_hien_canh_bao'],
            //             $data['thoi_gian_ket_thuc'],
            //         ]),
            //     $this->districtCache[$data['quanhuyen']] ?? null,
            //     $data['quanhuyen'] ?? null,
            //     $data['loai_tu'] ?? null,
            //     $data['ma_tu_btsnodeb'] ?? null,
            //     $data['ma_nha_tram_chuan'] ?? null,
            //     $data['ten_canh_bao'] ?? null,
            //     $data['thoi_gian_xuat_hien_canh_bao'],
            //     $data['thoi_gian_ket_thuc'],
            //     $data['thoi_gian_ton'] ?? null,
            //     $data['nhom_canh_bao'] ?? null,
            //     $data['nguyen_nhan'] ?? null,
            //     Str::contains($data['ma_tu_btsnodeb'], ['HCA', 'ehcs']) ? 1 : 0,
            //     $metrics['day_hours'] ?? null,
            //     $metrics['night_hours'] ?? null,
            //     $data['cellh_giam_tru'] ?? null,
            //     $data['nguyen_nhan'] ? (in_array($data['nguyen_nhan'], [
            //         'BKK impact follow Plan/CR VTNET',
            //         'BKK tác động theo KH/CR VTNET',
            //         'BKK Reset trạm tự động để sửa lỗi từ hệ thống SON, VMSA',
            //         'BKK tác động theo KH/CR CTCT',
            //         'Trạm mới/trạm chưa phát sóng',
            //         'Trạm mới phát sóng test, chưa đo kiểm nghiệm thu',
            //         'BKK tác động theo KH/CR CTCT',
            //         'Tác động theo KH/CR CTCT',
            //         'Tác động theo KH/CR VTNET',
            //     ]) ? 1 : 0) : '',
            //     $packed,
            //     $now,
            //     null,
            //     null,
            // ],

            'gdtt' => (
                (!empty($data['ma_tu_btsnodeb']) && Str::contains($data['ma_tu_btsnodeb'], ['HCA', 'ehcs']))
                ? []
                : [
                    $data['nhom_canh_bao'] == "Mất luồng" ?
                    implode(',', [
                        $data['ma_tu_btsnodeb'],
                        $data['thoi_gian_xuat_hien_canh_bao'],
                        $data['thoi_gian_ket_thuc'],
                    ]) : implode(',', [
                            $data['ten_cell'],
                            $data['thoi_gian_xuat_hien_canh_bao'],
                            $data['thoi_gian_ket_thuc'],
                        ]),
                    $this->districtCache[$data['quanhuyen']] ?? null,
                    $data['quanhuyen'] ?? null,
                    $data['loai_tu'] ?? null,
                    $data['ma_tu_btsnodeb'] ?? null,
                    $data['ma_nha_tram_chuan'] ?? null,
                    $data['ten_canh_bao'] ?? null,
                    $data['thoi_gian_xuat_hien_canh_bao'],
                    $data['thoi_gian_ket_thuc'],
                    $data['thoi_gian_ton'] ?? null,
                    $data['nhom_canh_bao'] ?? null,
                    $data['nguyen_nhan'] ?? null,
                    0,
                    $metrics['day_hours'] ?? null,
                    $metrics['night_hours'] ?? null,
                    $data['cellh_giam_tru'] ?? null,
                    $data['nguyen_nhan'] ? (in_array($data['nguyen_nhan'], [
                        'BKK impact follow Plan/CR VTNET',
                        'BKK tác động theo KH/CR VTNET',
                        'BKK Reset trạm tự động để sửa lỗi từ hệ thống SON, VMSA',
                        'BKK tác động theo KH/CR CTCT',
                        'Trạm mới/trạm chưa phát sóng',
                        'Trạm mới phát sóng test, chưa đo kiểm nghiệm thu',
                        'BKK tác động theo KH/CR CTCT',
                        'Tác động theo KH/CR CTCT',
                        'Tác động theo KH/CR VTNET',
                    ]) ? 1 : 0) : '',
                    $packed,
                    $now,
                    null,
                    null,
                ]
            ),

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
                self::ttkv($data['nhom_dieu_phoi']) ?? null,
                $data['trang_thai'] ?? null,
                Str::after($data['nhom_dieu_phoi'], 'TTKT Hồ Chí Minh_Đội Kỹ thuật ') ?? null,
                $data['thoi_diem_tao'],
                $data['thoi_diem_yeu_cau_ket_thuc_ddmmyyyy_hhmmss'],
                $data['thoi_diem_cd_dong'],
                $data['nhan_vien_thuc_hien'] ? $data['nhan_vien_thuc_hien'] : self::checkNV($data['nhom_dieu_phoi']),
                $data['thoi_diem_cd_dong'] ? ($metrics = $this->calculateWottMetrics($data['thoi_diem_cd_dong'], $data['thoi_diem_yeu_cau_ket_thuc_ddmmyyyy_hhmmss'], $data['muc_do_uu_tien']))[0] : null,
                $data['thoi_diem_cd_dong'] ? $metrics[1] : null,
                $data['thoi_diem_cd_dong'] ? $metrics[2] : null,
                // $metrics[1] ?? null,
                // $metrics[2] ?? null,
                $data['muc_do_uu_tien'] ?? null,
                $packed,
                $now,
                null,
                null
            ],
            'pakh' => [
                // $data['ma_cong_viec'] ?? null,
                // $data['ma_cong_viec'] ?? null,
                // $data['ma_tram'] ?? null,
                // self::ttkv($data['nhom_dieu_phoi']) ?? null,
                // $data['trang_thai'] ?? null,
                // Str::after($data['nhom_dieu_phoi'], 'TTKT Hồ Chí Minh_Đội Kỹ thuật ') ?? null,
                // $data['thoi_diem_tao'],
                // $data['thoi_diem_yeu_cau_ket_thuc_ddmmyyyy_hhmmss'],
                // $data['thoi_diem_cd_dong'],
                // $data['nhan_vien_thuc_hien'] ? $data['nhan_vien_thuc_hien'] : self::checkNV($data['nhom_dieu_phoi']),
                // $data['thoi_diem_cd_dong'] ? ($metrics = $this->calculateWottMetrics($data['thoi_diem_cd_dong'], $data['thoi_diem_yeu_cau_ket_thuc_ddmmyyyy_hhmmss'], $data['muc_do_uu_tien']))[0] : null,
                // $data['thoi_diem_cd_dong'] ? $metrics[1] : null,
                // $data['thoi_diem_cd_dong'] ? $metrics[2] : null,
                // $data['muc_do_uu_tien'] ?? null,
                // $packed,
                // $now,
                // null,
                // null
                $data['ma_cong_viec'] ?? null,
                $data['ma_cong_viec'] ?? null,
                $data['ma_tram'] ?? null,
                self::ttkv($data['nhom_dieu_phoi']) ?? null,
                $data['trang_thai'] ?? null,
                Str::after($data['nhom_dieu_phoi'], 'TTKT Hồ Chí Minh_Đội Kỹ thuật ') ?? null,
                $data['thoi_diem_tao'],
                $data['thoi_diem_yeu_cau_ket_thuc_ddmmyyyy_hhmmss'],
                $data['thoi_diem_cd_dong'],
                $data['nhan_vien_thuc_hien'] ? $data['nhan_vien_thuc_hien'] : self::checkNV($data['nhom_dieu_phoi']),
                $data['thoi_diem_cd_dong'] ? ($metrics = $this->calculateWottMetrics($data['thoi_diem_cd_dong'], $data['thoi_diem_yeu_cau_ket_thuc_ddmmyyyy_hhmmss'], $data['muc_do_uu_tien']))[0] : null,
                $data['thoi_diem_cd_dong'] ? $metrics[1] : null,
                $data['thoi_diem_cd_dong'] ? $metrics[2] : null,
                // $metrics[1] ?? null,
                // $metrics[2] ?? null,
                $data['muc_do_uu_tien'] ?? null,
                $packed,
                $now,
                null,
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
            'bonngdtt' => [
                $data['dau_vao'],
                $data['dau_vao'] ?? null,
                $data['muc_1'] ?? null,
                $data['giam_tru_muc_kv'] == '' ? 0 : $data['giam_tru_muc_kv'],
                $data['giam_tru_muc_tinh'] == '1' ? 1 : 0,
                $packed,
                $now,
                $now,
                null
            ],
            'tramuutiengdtt' => [
                implode(',', [
                    $data['ma_bts'],
                    $data['ma_nha_tram_chuan'],
                ]),
                $data['ma_nha_tram_chuan'] ?? null,
                $data['ma_bts'] ?? null,
                $data['cau_hinh'] == '' ? 0 : $data['cau_hinh'],
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
            'gdtt' => [
                'uuid',
                'ttkv',
                'quan',
                'loai_tu',
                'ma_tu_btsnodeb',
                'ma_nha_tram_chuan',
                'ten_canh_bao',
                'thoi_gian_xuat_hien_canh_bao',
                'thoi_diem_ket_thuc',
                'thoi_gian_ton',
                'nhom_canh_bao',
                'nguyen_nhan',
                'tram_small_cell',
                'tg_ngay',
                'tg_dem',
                'cellh_giam_tru',
                'kh_vtnetctct',
                'packed',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'sctd' => ['uuid', 'ttkv', 'huyen', 'ma_su_co', 'ngay_ps', 'phan_loai', 'loai_nn_lop_1', 'filter_data', 'packed', 'status', 'created_at', 'updated_at'],
            'wott' => [
                'uuid',
                'ma_cong_viec',
                'ma_tram',
                'ttkv',
                'trang_thai',
                'quan',
                'thoi_diem_bat_dau',
                'thoi_diem_ket_thuc',
                'thoi_diem_cd_dong',
                'nhan_vien_thuc_hien',
                'danh_gia_wo_thuc_hien',
                'time_status',
                'phat',
                'muc_do_uu_tien',
                'packed',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'pakh' => [
                // 'uuid',
                // 'ma_cong_viec',
                // 'ma_tram',
                // 'ttkv',
                // 'trang_thai',
                // 'quan',
                // 'thoi_diem_bat_dau',
                // 'thoi_diem_ket_thuc',
                // 'thoi_diem_cd_dong',
                // 'nhan_vien_thuc_hien',
                // 'danh_gia_wo_thuc_hien',
                // 'time_status',
                // 'phat',
                // 'muc_do_uu_tien',
                // 'packed',
                // 'created_at',
                // 'updated_at',
                // 'deleted_at',
                'uuid',
                'ma_cong_viec',
                'ma_tram',
                'ttkv',
                'trang_thai',
                'quan',
                'thoi_diem_bat_dau',
                'thoi_diem_ket_thuc',
                'thoi_diem_cd_dong',
                'nhan_vien_thuc_hien',
                'danh_gia_wo_thuc_hien',
                'time_status',
                'phat',
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
            ],
            'bonngdtt' => [
                'uuid',
                'dau_vao',
                'muc_1',
                'giam_tru_muc_kv',
                'giam_tru_muc_tinh',
                'packed',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'tramuutiengdtt' => [
                'uuid',
                'ma_nha_tram_chuan',
                'ma_bts',
                'cau_hinh',
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
            'bonngdtt' => '1_bo_n_n_g_d_t_t_s',
            'tramuutiengdtt' => '1_tram_uu_tien_g_d_t_t_s',
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

        if (is_numeric($timeString) && !str_contains($timeString, '/') && !str_contains($timeString, ':')) {
            try {
                $timestamp = ((float) $timeString - 25569) * 86400;
                return Carbon::createFromTimestampUTC($timestamp)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        }

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

    private function loadDistrictCache($problem): void
    {
        if (in_array($problem, ['wott', 'pakh'])) {

            $this->districtCache = District::with('area')
                ->get()
                ->mapWithKeys(fn($district) => [
                    Str::after($district->name2, 'TTKT Hồ Chí Minh_Đội Kỹ thuật ') => [
                        'nv_gan' => $district->area?->nv_gan,
                        'ttkv' => $district->area?->name,
                    ]
                ])
                ->all();
            // Log::info('District cache loaded:', $this->districtCache);
        } else if (in_array($problem, ['gdtt'])) {
            $this->districtCache = District::with('area')->get()->mapWithKeys(fn($district) => [
                $district->name => $district->area?->name,
            ])->all();
        }
    }
    private function checkNV(?string $quan): ?string
    {
        $key = Str::after(trim($quan), 'TTKT Hồ Chí Minh_Đội Kỹ thuật ');
        $result = $this->districtCache[$key]['nv_gan'] ?? null;
        return $result;
    }

    private function ttkv(?string $quan): ?string
    {
        $key = Str::after(trim($quan), 'TTKT Hồ Chí Minh_Đội Kỹ thuật ');
        $result = $this->districtCache[$key]['ttkv'] ?? null;
        return $result;
    }

    private function calculateWottMetrics(?string $dong, ?string $endTime, ?string $muc_do_uu_tien): array
    {
        if (!$dong || !$endTime)
            return [null, null, null];

        $dong = Carbon::parse($dong);
        $endTime = Carbon::parse($endTime);
        $diffInHours = $dong->diffInHours($endTime, false);


        // danh_gia_wo_thuc_hien
        $danhGia = match (true) {
            $diffInHours >= 24 => 'WO TH < 2 ngày',
            $diffInHours >= 0 && $diffInHours < 24 => 'WO TH < 1 ngày',
            $diffInHours <= -120 => 'WO QH > 5 ngày',
            $diffInHours <= -72 && $diffInHours > -120 => 'WO QH > 3 ngày',
            $diffInHours < 0 && $diffInHours > -72 => 'WO QH > 1 ngày',
            default => null,
        };

        // time_status
        $timeStatus = $diffInHours > 0 ? 'TH' : 'QH';

        // phat
        $phat = 0;
        if ($diffInHours < 0) {
            $completedHours = ceil(abs($diffInHours));
            $phat += ceil($completedHours / 24) * 50000;
            $daysOver = floor($completedHours / 24);
            if ($daysOver > 0 && $muc_do_uu_tien === 'Rất nghiêm trọng') {
                $phat += $daysOver * 500000;
            } elseif ($daysOver > 4 && $muc_do_uu_tien === 'Bình Thường') {
                $phat += ($daysOver - 5) * 500000;
            }
        }

        return [$danhGia, $timeStatus, $phat];
    }
    ////////////////    gdtt    //////////////////////
    // bonngdtt - dayhours - nighthours
    function splitDayNightHours(Carbon $start, float $totalHours): array
    {
        if ($totalHours <= 0) {
            return [
                'day_hours' => 0.0,
                'night_hours' => 0.0,
            ];
        }

        $end = $start->copy()->addSeconds($totalHours * 3600);

        $dayHours = 0.0;
        $nightHours = 0.0;

        if ($start->copy()->addSeconds($totalHours * 3600)->isSameDay($start)) {
            $current = $start->copy();

            while ($current < $end) {
                $hour = (int) $current->format('H');
                if ($hour < 5) {
                    $next = $current->copy()->startOfDay()->addHours(5);
                } elseif ($hour >= 5 && $hour < 24) {
                    $next = $current->copy()->startOfDay()->addDay();
                }

                if ($next > $end) {
                    $next = $end;
                }

                $duration = $next->diffInSeconds($current) / 3600;

                if ($hour >= 5 && $hour < 24) {
                    $dayHours += $duration;
                } else {
                    $nightHours += $duration;
                }

                $current = $next;
            }
        } else {
            $current = $start->copy();
            while ($current < $end) {
                $dayEnd = $current->copy()->endOfDay();
                if ($dayEnd > $end) {
                    $dayEnd = $end;
                }

                $remainingDayHours = $dayEnd->diffInSeconds($current) / 3600;

                $hour = (int) $current->format('H');
                if ($hour < 5) {
                    $to5am = min($remainingDayHours, (5 - $hour));
                    $nightHours += $to5am;
                    $dayHours += max(0, $remainingDayHours - $to5am);
                } elseif ($hour >= 5 && $hour < 24) {
                    $dayHours += $remainingDayHours;
                }

                $current = $dayEnd->copy()->addSecond();
            }
        }

        $rawTotal = $dayHours + $nightHours;
        if ($rawTotal == 0) {
            return [
                'day_hours' => 0.0,
                'night_hours' => 0.0,
            ];
        }

        $dayPercent = $dayHours / $rawTotal;
        $nightPercent = $nightHours / $rawTotal;

        $dayHoursResult = $totalHours * $dayPercent;
        $nightHoursResult = $totalHours * $nightPercent;

        $dayHoursResult = number_format($dayHoursResult, 2, '.', '');
        $nightHoursResult = number_format($nightHoursResult, 2, '.', '');

        $dayHoursResult = (float) $dayHoursResult;
        $nightHoursResult = (float) $nightHoursResult;

        return [
            'day_hours' => $dayHoursResult,
            'night_hours' => $nightHoursResult,
        ];
    }

    ////////////////    gdtt    //////////////////////



}
