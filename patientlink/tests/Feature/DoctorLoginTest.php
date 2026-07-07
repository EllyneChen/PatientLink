<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Facility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * FR-D01: The Doctor shall be able to log into the PatientLink system
     * using clinical credentials and receive a JWT token scoped to clinical access.
     */
    public function test_doctor_can_login_with_valid_credentials(): void
    {
        $facility = Facility::create([
            'name'     => 'Test Hospital',
            'location' => 'Nairobi',
            'api_key'  => 'test-key',
            'status'   => 'active',
        ]);

        $user = User::create([
            'name'      => 'Dr. Test User',
            'email'     => 'doctor@test.com',
            'password'  => bcrypt('password123'),
            'role'      => 'doctor',
            'is_active' => true,
        ]);

        Doctor::create([
            'user_id'        => $user->id,
            'facility_id'    => $facility->id,
            'licence_no'     => 'KEN-DR-00001',
            'specialisation' => 'General Medicine',
            'phone'          => '254700000000',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'doctor@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['access_token', 'token_type', 'role', 'user']);
        $this->assertEquals('doctor', $response->json('user.role'));
    }

    public function test_doctor_login_fails_with_invalid_password(): void
    {
        $user = User::create([
            'name'      => 'Dr. Test User Two',
            'email'     => 'doctor2@test.com',
            'password'  => bcrypt('correctpassword'),
            'role'      => 'doctor',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'doctor2@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }
}