<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\FacilityAdmin;
use App\Models\MohAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * PatientLinkSeeder
 * Seeds one test account per role so you can immediately test the
 * RBAC/JWT login endpoints without manually registering each role.
 *
 * Run with: php artisan db:seed --class=PatientLinkSeeder
 *
 * Test credentials (all passwords: "password"):
 *   doctor@patientlink.test          (Doctor)
 *   patient@patientlink.test         (Patient, NUPI: KE-2024-000123)
 *   facilityadmin@patientlink.test   (Facility Admin)
 *   mohadmin@patientlink.test        (MOH Admin)
 */
class PatientLinkSeeder extends Seeder
{
    public function run(): void
    {
        //  Facility 
        $facility = Facility::create([
            'name'     => 'Kenyatta National Hospital',
            'location' => 'Nairobi, Kenya',
            'api_key'  => bin2hex(random_bytes(16)),
            'status'   => 'active',
        ]);

        //  Doctor 
        $doctorUser = User::create([
            'name'     => 'Dr. Amina Osei',
            'email'    => 'doctor@patientlink.test',
            'password' => Hash::make('password'),
            'role'     => 'doctor',
        ]);

        Doctor::create([
            'user_id'        => $doctorUser->id,
            'facility_id'    => $facility->id,
            'licence_no'     => 'KEN-DR-20341',
            'specialisation' => 'General Medicine',
            'phone'          => '254712345001',
        ]);

        //  Patient
        $patientUser = User::create([
            'name'     => 'Wanjiku Muthoni',
            'email'    => 'patient@patientlink.test',
            'password' => Hash::make('password'),
            'role'     => 'patient',
        ]);

        Patient::create([
            'user_id'              => $patientUser->id,
            'nupi'                 => 'KE-2024-000123',
            'dob'                  => '1990-04-12',
            'phone'                => '254712345002',
            'next_of_kin_name'     => 'John Muthoni',
            'next_of_kin_phone'    => '254712345003',
            'data_sharing_consent' => true,
        ]);

        //  Facility Admin
        $facilityAdminUser = User::create([
            'name'     => 'Peter Kamau',
            'email'    => 'facilityadmin@patientlink.test',
            'password' => Hash::make('password'),
            'role'     => 'facility_admin',
        ]);

        FacilityAdmin::create([
            'user_id'     => $facilityAdminUser->id,
            'facility_id' => $facility->id,
            'admin_level' => 'standard',
        ]);

        //  MOH Admin
        $mohAdminUser = User::create([
            'name'     => 'James Otieno',
            'email'    => 'mohadmin@patientlink.test',
            'password' => Hash::make('password'),
            'role'     => 'moh_admin',
        ]);

        MohAdmin::create([
            'user_id'         => $mohAdminUser->id,
            'region'          => 'National',
            'clearance_level' => 5,
        ]);

        $this->command->info('PatientLink seed data created successfully.');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Doctor',         'doctor@patientlink.test',        'password'],
                ['Patient',        'patient@patientlink.test',       'password'],
                ['Facility Admin', 'facilityadmin@patientlink.test', 'password'],
                ['MOH Admin',      'mohadmin@patientlink.test',      'password'],
            ]
        );
    }
}
