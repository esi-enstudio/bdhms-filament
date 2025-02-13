<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define Permissions
        $permissions = [
            ['name' => 'view user', 'group_name' => 'user', 'guard_name' => 'web'],
            ['name' => 'create user', 'group_name' => 'user', 'guard_name' => 'web'],
            ['name' => 'edit user', 'group_name' => 'user', 'guard_name' => 'web'],
            ['name' => 'show user', 'group_name' => 'user', 'guard_name' => 'web'],
            ['name' => 'delete user', 'group_name' => 'user', 'guard_name' => 'web'],

            ['name' => 'view house', 'group_name' => 'house', 'guard_name' => 'web'],
            ['name' => 'create house', 'group_name' => 'house', 'guard_name' => 'web'],
            ['name' => 'edit house', 'group_name' => 'house', 'guard_name' => 'web'],
            ['name' => 'show house', 'group_name' => 'house', 'guard_name' => 'web'],
            ['name' => 'delete house', 'group_name' => 'house', 'guard_name' => 'web'],

            ['name' => 'view rso', 'group_name' => 'rso', 'guard_name' => 'web'],
            ['name' => 'create rso', 'group_name' => 'rso', 'guard_name' => 'web'],
            ['name' => 'edit rso', 'group_name' => 'rso', 'guard_name' => 'web'],
            ['name' => 'show rso', 'group_name' => 'rso', 'guard_name' => 'web'],
            ['name' => 'delete rso', 'group_name' => 'rso', 'guard_name' => 'web'],
            ['name' => 'download document rso', 'group_name' => 'rso', 'guard_name' => 'web'],

            ['name' => 'view retailer', 'group_name' => 'retailer', 'guard_name' => 'web'],
            ['name' => 'create retailer', 'group_name' => 'retailer', 'guard_name' => 'web'],
            ['name' => 'edit retailer', 'group_name' => 'retailer', 'guard_name' => 'web'],
            ['name' => 'show retailer', 'group_name' => 'retailer', 'guard_name' => 'web'],
            ['name' => 'delete retailer', 'group_name' => 'retailer', 'guard_name' => 'web'],
            ['name' => 'download document retailer', 'group_name' => 'retailer', 'guard_name' => 'web'],
            ['name' => 'import retailer', 'group_name' => 'retailer', 'guard_name' => 'web'],
            ['name' => 'export retailer', 'group_name' => 'retailer', 'guard_name' => 'web'],
            ['name' => 'delete all retailer', 'group_name' => 'retailer', 'guard_name' => 'web'],

            ['name' => 'view replace', 'group_name' => 'replace', 'guard_name' => 'web'],
            ['name' => 'create replace', 'group_name' => 'replace', 'guard_name' => 'web'],
            ['name' => 'edit replace', 'group_name' => 'replace', 'guard_name' => 'web'],
            ['name' => 'show replace', 'group_name' => 'replace', 'guard_name' => 'web'],
            ['name' => 'delete replace', 'group_name' => 'replace', 'guard_name' => 'web'],

            ['name' => 'view role', 'group_name' => 'role', 'guard_name' => 'web'],
            ['name' => 'create role', 'group_name' => 'role', 'guard_name' => 'web'],
            ['name' => 'edit role', 'group_name' => 'role', 'guard_name' => 'web'],
            ['name' => 'show role', 'group_name' => 'role', 'guard_name' => 'web'],
            ['name' => 'delete role', 'group_name' => 'role', 'guard_name' => 'web'],

            ['name' => 'view permission', 'group_name' => 'permission', 'guard_name' => 'web'],
            ['name' => 'create permission', 'group_name' => 'permission', 'guard_name' => 'web'],
            ['name' => 'edit permission', 'group_name' => 'permission', 'guard_name' => 'web'],
            ['name' => 'show permission', 'group_name' => 'permission', 'guard_name' => 'web'],
            ['name' => 'delete permission', 'group_name' => 'permission', 'guard_name' => 'web'],

            ['name' => 'view commission', 'group_name' => 'commission', 'guard_name' => 'web'],
            ['name' => 'create commission', 'group_name' => 'commission', 'guard_name' => 'web'],
            ['name' => 'edit commission', 'group_name' => 'commission', 'guard_name' => 'web'],
            ['name' => 'show commission', 'group_name' => 'commission', 'guard_name' => 'web'],
            ['name' => 'delete commission', 'group_name' => 'commission', 'guard_name' => 'web'],

            ['name' => 'view lifting', 'group_name' => 'lifting', 'guard_name' => 'web'],
            ['name' => 'create lifting', 'group_name' => 'lifting', 'guard_name' => 'web'],
            ['name' => 'edit lifting', 'group_name' => 'lifting', 'guard_name' => 'web'],
            ['name' => 'show lifting', 'group_name' => 'lifting', 'guard_name' => 'web'],
            ['name' => 'delete lifting', 'group_name' => 'lifting', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission){
            Permission::firstOrCreate($permission);
        }

        $superAdmin = Role::create(['name' => 'super admin']);
        User::findOrFail(1)->assignRole('super admin');

        $zm = Role::create(['name' => 'zm']);
        $zm->givePermissionTo(['view house', 'create house', 'edit house', 'delete house','view rso', 'create rso', 'edit rso', 'delete rso', 'view retailer', 'create retailer', 'edit retailer', 'delete retailer',]);
        User::findOrFail(2)->assignRole('zm');

        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo(['view rso', 'create rso', 'edit rso', 'delete rso', 'view retailer', 'create retailer', 'edit retailer', 'delete retailer','view commission', 'create commission', 'edit commission', 'delete commission',]);
        User::findOrFail(4)->assignRole('manager');
        User::findOrFail(5)->assignRole('manager');
        User::findOrFail(6)->assignRole('manager');

        $supervisor = Role::create(['name' => 'supervisor']);
        $supervisor->givePermissionTo(['view rso', 'create rso', 'edit rso', 'view retailer', 'create retailer', 'edit retailer', 'view commission', 'create commission', 'edit commission',]);
        User::findOrFail(7)->assignRole('supervisor');
        User::findOrFail(8)->assignRole('supervisor');
        User::findOrFail(9)->assignRole('supervisor');

        $rso = Role::create(['name' => 'rso']);
        $rso->givePermissionTo(['view rso', 'edit rso', 'view retailer', 'edit retailer', 'view commission','view replace', 'create replace', 'edit replace',]);
        User::findOrFail(10)->assignRole('rso');
        User::findOrFail(11)->assignRole('rso');
        User::findOrFail(12)->assignRole('rso');
        User::findOrFail(13)->assignRole('rso');
        User::findOrFail(14)->assignRole('rso');
        User::findOrFail(15)->assignRole('rso');
        User::findOrFail(16)->assignRole('rso');
        User::findOrFail(17)->assignRole('rso');
        User::findOrFail(18)->assignRole('rso');
        User::findOrFail(19)->assignRole('rso');
        User::findOrFail(20)->assignRole('rso');
        User::findOrFail(21)->assignRole('rso');
        User::findOrFail(22)->assignRole('rso');
        User::findOrFail(23)->assignRole('rso');
        User::findOrFail(24)->assignRole('rso');
        User::findOrFail(25)->assignRole('rso');
        User::findOrFail(26)->assignRole('rso');
        User::findOrFail(27)->assignRole('rso');
        User::findOrFail(28)->assignRole('rso');
        User::findOrFail(29)->assignRole('rso');
        User::findOrFail(30)->assignRole('rso');
        User::findOrFail(31)->assignRole('rso');
        User::findOrFail(32)->assignRole('rso');
        User::findOrFail(33)->assignRole('rso');
        User::findOrFail(34)->assignRole('rso');
        User::findOrFail(35)->assignRole('rso');

        $rso = Role::create(['name' => 'retailer']);
        $rso->givePermissionTo(['view retailer', 'view replace']);
        User::findOrFail(36)->assignRole('retailer');
        User::findOrFail(37)->assignRole('retailer');
        User::findOrFail(38)->assignRole('retailer');
        User::findOrFail(39)->assignRole('retailer');
        User::findOrFail(40)->assignRole('retailer');
        User::findOrFail(41)->assignRole('retailer');
        User::findOrFail(42)->assignRole('retailer');
        User::findOrFail(43)->assignRole('retailer');
        User::findOrFail(44)->assignRole('retailer');
        User::findOrFail(45)->assignRole('retailer');
        User::findOrFail(46)->assignRole('retailer');
        User::findOrFail(47)->assignRole('retailer');
        User::findOrFail(48)->assignRole('retailer');
        User::findOrFail(49)->assignRole('retailer');
        User::findOrFail(50)->assignRole('retailer');
        User::findOrFail(51)->assignRole('retailer');
        User::findOrFail(52)->assignRole('retailer');
        User::findOrFail(53)->assignRole('retailer');
        User::findOrFail(54)->assignRole('retailer');
        User::findOrFail(55)->assignRole('retailer');
        User::findOrFail(56)->assignRole('retailer');
        User::findOrFail(57)->assignRole('retailer');
        User::findOrFail(58)->assignRole('retailer');
        User::findOrFail(59)->assignRole('retailer');
        User::findOrFail(60)->assignRole('retailer');
        User::findOrFail(61)->assignRole('retailer');
        User::findOrFail(62)->assignRole('retailer');
    }
}
