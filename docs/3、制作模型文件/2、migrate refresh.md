```
php artisan migrate:refresh --seed
```

## 标准的DatabaseSeeder.php文件
```php
<?php  
  
namespace Database\Seeders;  
  
use Illuminate\Database\Seeder;  
use Illuminate\Support\Facades\Artisan;  
  
class DatabaseSeeder extends Seeder  
{  
    /**  
     * Seed the application's database.     
    */
    public function run(): void  
    {  
        Artisan::call('cdb'); // 缓存所有数据库表的结构，代码生成靠他  
        Artisan::call('ce'); // 缓存所有枚举结构，代码生成靠他
        Artisan::call('gam'); // 重新生成所有model文件
  
        Artisan::call('InitDBCommand'); // 初始化数据  

		// 这里放一些表的初始化数据
        $this->call(PersonalAccessTokensTableSeeder::class);  
    }  
}
```

## 一般情况下，可以写一个InitDBCommand，用Model去做初始化
```php
<?php  
  
namespace App\Console\Commands;  
  
use App\Enums\SysModuleEnum;  
use App\Models\Admins;  
use App\Models\Companies;  
use App\Models\CompanyAdmins;  
use App\Models\SysPermissions;  
use App\Models\SysRoles;  
use Illuminate\Console\Command;  
use Illuminate\Support\Facades\DB;  
use Spatie\Permission\Models\Role;  
  
class InitDBCommand extends Command  
{  
    protected $signature = 'InitDBCommand';  
    protected $description = 'Command description';  
  
    /**  
     * @return void  
     */    public function handle(): void  
    {  
        DB::transaction(function () {  
            $this->handleDB();  
        });  
    }  
  
    /**  
     * @return void  
     */    public function handleDB(): void  
    {  
        $admin1 = Admins::create([  
            'username' => 'zwb',  
            'password' => bcrypt('123123'),  
        ]);  
        $admin2 = Admins::create([  
            'username' => 'zsq',  
            'password' => bcrypt('123123'),  
        ]);  
        $admin3 = Admins::create([  
            'username' => 'wjh',  
            'password' => bcrypt('123123'),  
        ]);  
  
        Companies::create([  
            'name' => '测试物业公司', #  
            'prefix' => 'test', #  
            'full_name' => '深圳市测试物业有线公司', #  
            'license_number' => '123'  
        ]);  
  
        CompanyAdmins::create([  
            'companies_id' => 1, # 公司id,[ref:companies]  
            'username' => 'cswy', # 用户名  
            'password' => bcrypt('123123'),  
            'is_super' => true, # 是否超级管理员  
        ]);  
  
        CompanyAdmins::create([  
            'companies_id' => 1, # 公司id,[ref:companies]  
            'username' => 'wy1', # 用户名  
            'password' => bcrypt('123123'),  
            'is_super' => true, # 是否超级管理员  
        ]);  
  
        $role1 = SysRoles::create([  
            'name' => '管理员', #  
            'guard_name' => SysModuleEnum::Admin->value, #  
            'color' => '#FF0000', # 颜色  
        ]);  
        $admin1->syncRoles([$role1->id]);  
        $admin2->syncRoles([$role1->id]);  
        $admin3->syncRoles([$role1->id]);  
  
//        $role2 = SysRoles::create([  
//            'name' => '机构管理员', #  
//            'guard_name' => SysModuleEnum::Company->value, #  
//            'companies_id' => 1, # 公司id,[ref:companies]  
//            'color' => '#FF0000', # 颜色  
//        ]);  
//        $admin4->syncRoles([$role2->id]);  
  
        SysPermissions::create([  
            'name' => '总后台', #  
            'type' => 'Directory',  
            'guard_name' => SysModuleEnum::Admin->value, #  
            'children' => [  
                [  
                    'name' => '机构', #  
                    'type' => 'Directory',  
                    'icon' => 'ShopOutlined',  
                    'guard_name' => SysModuleEnum::Admin->value, #  
                    'children' => [  
                        [  
                            'name' => '机构管理', #  
                            'path' => '/company/companies',  
                            'guard_name' => SysModuleEnum::Admin->value, #  
                        ],  
                        [  
                            'name' => '机构管理员', #  
                            'path' => '/company/company_admins',  
                            'guard_name' => SysModuleEnum::Admin->value, #  
                        ],  
                        [  
                            'name' => '机构员工', #  
                            'path' => '/company/company_employees',  
                            'guard_name' => SysModuleEnum::Admin->value, #  
                        ]  
                    ],  
                ],  
                [  
                    'name' => '系统', #  
                    'type' => 'Directory',  
                    'icon' => 'SettingOutlined',  
                    'guard_name' => SysModuleEnum::Admin->value, #  
                    'children' => [  
                        [  
                            'name' => '管理员管理', #  
                            'path' => '/system/admins', # 菜单链接  
                            'guard_name' => SysModuleEnum::Admin->value, #  
                        ],  
                        [  
                            'name' => '角色管理', #  
                            'path' => '/system/sys_roles', # 菜单链接  
                            'guard_name' => SysModuleEnum::Admin->value, #  
                        ],  
                        [  
                            'name' => '功能管理', #  
                            'path' => '/system/sys_permissions', # 菜单链接  
                            'guard_name' => SysModuleEnum::Admin->value, #  
                        ],  
                    ],  
                ]  
            ]  
        ]);  
  
        SysPermissions::create([  
            'name' => '机构后台', #  
            'type' => 'Directory',  
            'guard_name' => SysModuleEnum::Company->value, #  
            'children' => [  
                [  
                    'name' => '机构', #  
                    'type' => 'Directory',  
                    'icon' => 'ShopOutlined',  
                    'guard_name' => SysModuleEnum::Company->value, #  
                    'children' => [  
                        [  
                            'name' => '机构信息编辑', #  
                            'path' => '/company/companies/show',  
                            'guard_name' => SysModuleEnum::Company->value, #  
                        ],  
                        [  
                            'name' => '机构员工', #  
                            'path' => '/company/company_employees',  
                            'guard_name' => SysModuleEnum::Company->value, #  
                        ],  
                        [  
                            'name' => '机构项目管理', #  
                            'path' => '/company/company_projects',  
                            'guard_name' => SysModuleEnum::Company->value, #  
                        ]  
                    ],  
                ],  
                [  
                    'name' => '系统', #  
                    'type' => 'Directory',  
                    'icon' => 'SettingOutlined',  
                    'guard_name' => SysModuleEnum::Company->value, #  
                    'children' => [  
                        [  
                            'name' => '管理员管理', #  
                            'path' => '/system/company_admins', # 菜单链接  
                            'guard_name' => SysModuleEnum::Company->value, #  
                        ],  
                        [  
                            'name' => '角色管理', #  
                            'path' => '/system/sys_roles', # 菜单链接  
                            'guard_name' => SysModuleEnum::Company->value, #  
                        ],  
                    ],  
                ]  
            ],  
        ]);  
  
        Role::where('name', '管理员')->first()->syncPermissions(SysPermissions::where('guard_name', SysModuleEnum::Admin->value)->pluck('id')->toArray());  
//        Role::where('name', '机构管理员')->first()->syncPermissions(SysPermissions::where('guard_name', SysModuleEnum::Company->value)->pluck('id')->toArray());  
    }  
}
```