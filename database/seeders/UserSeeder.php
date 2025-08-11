<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Institution;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1️⃣ Create Admin
        User::create([
            'name'       => 'Admin User',
            'email'      => 'admin@example.com',
            'password'   => Hash::make('password'),
            'user_uid'   => (string) Str::uuid(),
            'role'       => 'admin',
            'is_active'  => true,
            'is_verified' => true,
        ]);

        // 2️⃣ Create Member
        $member = User::create([
            'name'       => 'Member User',
            'email'      => 'member@example.com',  // official email for member
            'email_verified_at' => now(), // Set email verification date
            'password'   => Hash::make('password'),
            'user_uid'   => (string) Str::uuid(),
            'role'       => 'member',
            'designation'               => 'CEO',
            'phone_number'              => '+2348012345678',
            'is_active'  => true,
            'is_verified' => true,
        ]);

        // 3️⃣ Create Institution for Member
        Institution::create([
            'institution_uid'          => (string) Str::uuid(),
            'user_id'                   => $member->id,
            'institution_name'          => 'ABC Microfinance',
            'institution_type'          => 'Microfinance',
            'category_type'             => 'unit',
            'date_of_establishment'     => '2020-05-15',
            'registration_number'       => 'REG-123456',
            'regulatory_body'           => 'Central Bank',
            'operating_state'           => 'Lagos',
            'head_office'               => '123 Main Street, Lagos',
            'business_operation_address' => '45 Business Rd, Abuja',
            'website_url'               => 'https://www.example.com',
            'descriptions'              => 'A cooperative offering microfinance services.',

            // File paths - these are just sample strings
            'certificate_of_registration' => 'uploads/institutions/sample/certificate.pdf',
            'operational_license'         => 'uploads/institutions/sample/license.pdf',
            'constitution'                 => 'uploads/institutions/sample/constitution.pdf',
            'latest_annual_report'         => 'uploads/institutions/sample/annual_report.pdf',
            'letter_of_intent'             => 'uploads/institutions/sample/letter_of_intent.pdf',
            'board_resolution'             => 'uploads/institutions/sample/board_resolution.pdf',
            'passport_photograph'          => 'uploads/institutions/sample/passport.jpg',
            'other_supporting_document'    => 'uploads/institutions/sample/supporting_doc.pdf',

            'membership_agreement' => true,
            'terms_agreement'      => true,
            'is_approved'          => 1,
        ]);
    }
}
