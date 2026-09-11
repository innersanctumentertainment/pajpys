<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';
        $permissions = ['users.view','users.manage','jobs.view','jobs.manage','services.view','services.manage','disputes.view','disputes.manage','payments.view','payments.manage','withdrawals.view','withdrawals.manage','verifications.view','verifications.manage','reports.view','reports.manage','reviews.view','reviews.manage','platform_settings.view','platform_settings.manage','cms.view','cms.manage','audit_logs.view','staff_notes.manage'];
        foreach ($permissions as $permission) { Permission::findOrCreate($permission, $guard); }
        $masterAdmin = Role::findOrCreate('master_admin', $guard);
        $staff = Role::findOrCreate('staff', $guard);
        Role::findOrCreate('client', $guard); Role::findOrCreate('provider', $guard); Role::findOrCreate('va', $guard);
        $masterAdmin->syncPermissions(Permission::all());
        $staff->syncPermissions(['users.view','users.manage','jobs.view','jobs.manage','services.view','services.manage','disputes.view','disputes.manage','payments.view','withdrawals.view','withdrawals.manage','verifications.view','verifications.manage','reports.view','reports.manage','reviews.view','cms.view','audit_logs.view','staff_notes.manage']);
    }
}
