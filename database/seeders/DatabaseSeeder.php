<?php

namespace Database\Seeders;

use App\Enum\PermissionsEnum;
use App\Enum\RolesEnum;


use App\Models\Dashboard_And_Reports\Area;
use App\Models\Dashboard_And_Reports\District;
use App\Models\Dashboard_And_Reports\QLT;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(200)->create();

        // $userRole = Role::create(['name' => RolesEnum::User->value, 'created_by' => 'Super Admin']);
        // $commenterRole = Role::create(['name' => RolesEnum::Commenter->value, 'created_by' => 'Super Admin']);


        $data = [
            'Dashboard & Reports' => [
                'file manager' => [
                    'view file manager',
                    'create file manager',
                    'edit file manager',
                    'update file manager',
                    'destroy file manager',
                ],
                'areas' => [
                    'view areas',
                    'create areas',
                    'edit areas',
                    'update areas',
                    'destroy areas',
                ],
                'dashboard' => [
                    'view dashboard',
                ],
            ],
            'User Management' => [
                'permissions' => [
                    'view permissions',
                    'create permissions',
                    'edit permissions',
                    'update permissions',
                    'destroy permissions',
                ],
                'roles' => [
                    'view roles',
                    'create roles',
                    'edit roles',
                    'update roles',
                    'destroy roles',
                ],
                'users' => [
                    'view users',
                    'create users',
                    'edit users',
                    'update users',
                    'destroy users',
                ],
            ],
            'Content' => [
                'gdtt' => [
                    'view gdtt',
                    'create gdtt',
                    'edit gdtt',
                    'update gdtt',
                    'destroy gdtt',
                ],
                'sctd' => [
                    'view sctd',
                    'create sctd',
                    'edit sctd',
                    'update sctd',
                    'destroy sctd',
                ],
                'cdbr' => [
                    'view cdbr',
                    'create cdbr',
                    'edit cdbr',
                    'update cdbr',
                    'destroy cdbr',
                ],
                'wott' => [
                    'view wott',
                    'create wott',
                    'edit wott',
                    'update wott',
                    'destroy wott',
                ],
                'pakh' => [
                    'view pakh',
                    'create pakh',
                    'edit pakh',
                    'update pakh',
                    'destroy pakh',
                ],
            ],
        ];
        $value = [];
        foreach ($data as $key => $framework) {
            foreach ($framework as $key1 => $value1) {
                foreach ($value1 as $value2) {
                    $value[$key][$key1][$value2] =
                        Permission::create([
                            'name' => $value2,
                            'name1' => Str::after($value2, ' '),
                            'framework' => $key,
                            'created_by' => "Super Admin",
                        ]);
                }
            }
        }
        // dd($value);

        $adminRole = Role::create(['name' => RolesEnum::Admin->value, 'created_by' => 'Super Admin']);  //admin
        Role::create(['name' => 'Super Admin', 'created_by' => 'Super Admin']);                         //super admin
        $userRole = Role::create(['name' => RolesEnum::User->value, 'created_by' => 'Super Admin']);    //user

        // $userRole->syncPermissions([$value['Application']['file manager'], $value['Application']['areas']]);
        $userRole->syncPermissions([
            $value['Dashboard & Reports'],
            $value['Content']
        ]);
        $adminRole->syncPermissions([
            $value['Dashboard & Reports'],
            $value['Content'],
            $value['User Management'],
        ]);

        // $userRole->syncPermissions([$value['Application']['file manager'], $value['Application']['areas']]);
        // $adminRole->syncPermissions([
        //     $value['Application'],
        //     $value['Permission'],
        //     $value['Problem'],
        // ]);


        // $userRole->syncPermissions($value[]);
        // $commenterRole->syncPermissions([$upvoteDownvotePermission, $manageCommentsPermission]);
        // $adminRole->syncPermissions([
        //     $upvoteDownvotePermission,
        //     $manageUserPermission,
        //     $manageCommentsPermission,
        //     $manageFeaturesPermission,
        // ]);

        // $manageFeaturesPermission = Permission::create([
        //     'name' => PermissionsEnum::ManageFeatures->value,
        // ]);
        // $manageCommentsPermission = Permission::create([
        //     'name' => PermissionsEnum::ManageComments->value,
        // ]);
        // $manageUserPermission = Permission::create([
        //     'name' => PermissionsEnum::ManageUser->value,
        // ]);
        // $upvoteDownvotePermission = Permission::create([
        //     'name' => PermissionsEnum::UpvoteDownvote->value,
        // ]);

        // $userRole->syncPermissions([$upvoteDownvotePermission]);
        // // $commenterRole->syncPermissions([$upvoteDownvotePermission, $manageCommentsPermission]);
        // $adminRole->syncPermissions([
        //     $upvoteDownvotePermission,
        //     $manageUserPermission,
        //     $manageCommentsPermission,
        //     $manageFeaturesPermission,
        // ]);

        User::factory()->create([
            'name' => 'User User',
            'email' => 'user@example.com',
            'password' => 12345678,
        ])->assignRole(RolesEnum::User);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 12345678,
        ])->assignRole(RolesEnum::Admin);


        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => 12345678,
        ])->assignRole('Super Admin');

        $nv_gan = [
            'SGN' => 'Trần Mạnh Khang',
            'NSG' => 'Nguyễn Văn Trung',
            'PTO' => 'Nguyễn Bình An',
            'TSG' => 'Phạm Minh Tâm',
            'BSG' => 'Lê Quang Hưng',
            'GĐH' => 'Lương Văn Thành',
            'TĐC' => 'Trần Lê Phương Linh',
        ];



        $area = [
            'SGN' => [
                [
                    'name' => "Q.01",
                    'name1' => "Q01",
                    'name2' => 'Quận 1'
                ],
                [
                    'name' => "Q.03",
                    'name1' => "Q03",
                    'name2' => 'Quận 3'
                ],
                [
                    'name' => "Q.04",
                    'name1' => "Q04",
                    'name2' => 'Quận 4'
                ],
                [
                    'name' => "Q.05",
                    'name1' => "Q05",
                    'name2' => 'Quận 5'
                ],
                [
                    'name' => "Q.10",
                    'name1' => "Q10",
                    'name2' => 'Quận 10'
                ],
            ],
            'NSG' => [
                [
                    'name' => "Q.07",
                    'name1' => "Q07",
                    'name2' => 'Quận 7'
                ],
                [
                    'name' => "Q.08",
                    'name1' => "Q08",
                    'name2' => 'Quận 8'
                ],
                [
                    'name' => "H.Cần Giờ",
                    'name1' => "CGO",
                    'name2' => 'Cần Giờ'
                ],
                [
                    'name' => "H.Nhà Bè",
                    'name1' => "NBE",
                    'name2' => 'Nhà Bè'
                ],
            ],
            'PTO' => [
                [
                    'name' => "Q.11",
                    'name1' => "Q11",
                    'name2' => 'Quận 11'
                ],
                [
                    'name' => "Q.Tân Phú",
                    'name1' => "TPU",
                    'name2' => 'Tân Phú'
                ],
                [
                    'name' => "Q.Tân Bình",
                    'name1' => "TBH",
                    'name2' => 'Tân Bình'
                ],

            ],
            'TSG' => [
                [
                    'name' => "Q.06",
                    'name1' => "Q06",
                    'name2' => 'Quận 6'
                ],
                [
                    'name' => "H.Bình Chánh",
                    'name1' => "BCH",
                    'name2' => 'Bình Chánh'
                ],
                [
                    'name' => "Q.Bình Tân",
                    'name1' => "BTN",
                    'name2' => 'Bình Tân'
                ]
            ],
            'BSG' => [
                [
                    'name' => "Q.12",
                    'name1' => "Q12",
                    'name2' => 'Quận 12'
                ],
                [
                    'name' => "H.Củ Chi",
                    'name1' => "CCI",
                    'name2' => 'Củ Chi'
                ],
                [
                    'name' => "H.Hóc Môn",
                    'name1' => "HMN",
                    'name2' => 'Hóc Môn'
                ]
            ],
            'GĐH' => [
                [
                    'name' => "Q.Bình Thạnh",
                    'name1' => "BTH",
                    'name2' => 'Bình Thạnh'
                ],
                [
                    'name' => "Q.Gò Vấp",
                    'name1' => "GVP",
                    'name2' => 'Gò Vấp'
                ],
                [
                    'name' => "Q.Phú Nhuận",
                    'name1' => "PNN",
                    'name2' => 'Phú Nhuận'
                ]
            ],
            'TĐC' => [
                [
                    'name' => "Q.02",
                    'name1' => "Q02",
                    'name2' => 'Quận 2'
                ],
                [
                    'name' => "Q.09",
                    'name1' => "Q09",
                    'name2' => 'Quận 9'
                ],
                [
                    'name' => "Tp.Thủ Đức",
                    'name1' => "TDC",
                    'name2' => ''
                ],
                [
                    'name' => "Q.Thủ Đức",
                    'name1' => "TĐC",
                    'name2' => 'Thủ Đức'
                ]
            ],
        ];

        foreach ($area as $key => $value) {
            QLT::create(
                [
                    'ma_tram' => $key,
                    'ttkv' => $key,
                    'quan' => $area[$key][0]['name2'],
                    'user_vt' => $nv_gan[$key],
                ]
            );
            $createdArea = Area::factory()->create([
                'name' => $key,
                'created_by' => auth()->user(),
                'nv_gan' => $nv_gan[$key],
            ]);

            foreach ($value as $subArea) {
                District::factory()->create([
                    'name' => $subArea['name'],
                    'name1' => $subArea['name1'] ?? null,
                    'name2' => $subArea['name2'] ?? null,
                    'area_id' => $createdArea->id,
                    'area_name' => $createdArea->name,
                ]);
            }
        }


    }
}
