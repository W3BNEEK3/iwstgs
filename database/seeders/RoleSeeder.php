<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
              'name' => 'super_admin',
              'description' => 'Full system access. Can access all Organisation and content'],
            [
              'name' => 'org_admin',
              'description' => 'Organisation scoped admin'],
            [
              'name' => 'content_author',
              'description' => 'Can create and manage projects, scenarios, tasks, and rubric criteria.'],
            [
              'name' => 'learner',
              'description' => 'Can enrole in projects and paeticipate in scenarios']
        ];
        
        forEach ($roles as $role)
        {
            DB::table('roles')->insertOrIgnore($role);
        }
    }
}
