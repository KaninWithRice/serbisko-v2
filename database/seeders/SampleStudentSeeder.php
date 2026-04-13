<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Faker\Factory as Faker;

class SampleStudentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // 0. Ensure System Settings exist
        $activeSY = '2025-2026';
        SystemSetting::updateOrCreate(
            ['id' => 1],
            [
                'active_school_year' => $activeSY,
                'active_sheet_range' => 'Form Responses 1!A1:ZZ',
                'public_form_url' => 'https://docs.google.com/forms/sample',
            ]
        );

        // Clean up before seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('students')->truncate();
        DB::table('kiosk_enrollments')->truncate();
        DB::table('scans')->truncate();
        DB::table('pre_enrollments')->truncate();
        User::where('role', 'student')->forceDelete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $sampleImages = Storage::disk('public')->files('scans');
        
        // Define document requirements based on Student Kind (Type)
        $tracks = [
            'Regular'    => ['Report Card (SF9)', 'Birth Certificate', 'Enrollment Form'],
            'ALS'        => ['ALS Certificate', 'Enrollment Form', 'Birth Certificate', 'Affidavit'],
            'Transferee' => ['Report Card (SF9)', 'Birth Certificate', 'Affidavit', 'Enrollment Form'],
            'Balik-Aral' => ['Report Card (SF9)', 'Birth Certificate', 'Affidavit', 'Enrollment Form'],
        ];

        $studentKinds = ['Regular', 'ALS', 'Transferee', 'Balik-Aral'];

        // 1. Create 4 Registered Students (One of each Kind)
        foreach ($studentKinds as $index => $kind) {
            $firstName = ($index === 0) ? 'Mary Grace' : $faker->firstName;
            $lastName = ($index === 0) ? 'Dellomos' : $faker->lastName;
            $middleName = ($index === 0) ? null : $faker->lastName;
            $birthday = ($index === 0) ? '0202-02-02' : $faker->date('Y-m-d', '2009-12-31');

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'middle_name' => $middleName,
                'birthday' => $birthday,
                'role' => 'student',
                'password' => Hash::make('password'),
            ]);

            $lrn = "10000000000" . ($index + 1);
            $studentId = DB::table('students')->insertGetId([
                'lrn' => $lrn,
                'user_id' => $user->id,
                'school_year' => $activeSY,
                'sex' => $faker->randomElement(['Male', 'Female']),
                'age' => $faker->numberBetween(15, 18),
                'place_of_birth' => $faker->city,
                'mother_tongue' => 'Filipino',
                'guardian_last_name' => $user->last_name,
                'guardian_first_name' => $faker->firstName,
                'guardian_contact_number' => $faker->phoneNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Kind is stored in the Pre-enrollment data (from Google Sheets)
            DB::table('pre_enrollments')->insert([
                'student_id' => $studentId,
                'responses' => json_encode([
                    'Academic Status' => $kind, // This is the STUDENT KIND (Regular, ALS, etc.)
                    'Grade Level to Enroll' => 'Grade 11',
                    'Track' => 'Academic',
                    'Cluster of Electives' => 'Science, Technology, Engineering, and Mathematics (STEM)'
                ]),
                'status' => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Create 4 Officially Enrolled Students (One of each Kind)
        foreach ($studentKinds as $index => $kind) {
            $user = User::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'middle_name' => $faker->lastName,
                'birthday' => $faker->date('Y-m-d', '2008-12-31'),
                'role' => 'student',
                'password' => Hash::make('password'),
            ]);

            $lrn = "20000000000" . ($index + 1);
            $studentId = DB::table('students')->insertGetId([
                'lrn' => $lrn,
                'user_id' => $user->id,
                'school_year' => $activeSY,
                'sex' => $faker->randomElement(['Male', 'Female']),
                'age' => $faker->numberBetween(16, 19),
                'place_of_birth' => $faker->city,
                'mother_tongue' => 'Filipino',
                'guardian_last_name' => $user->last_name,
                'guardian_first_name' => $faker->firstName,
                'guardian_contact_number' => $faker->phoneNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Pre-enrollment data - Still has the original kind
            DB::table('pre_enrollments')->insert([
                'student_id' => $studentId,
                'responses' => json_encode([
                    'Academic Status' => $kind,
                    'Grade Level to Enroll' => 'Grade 11',
                    'Track' => 'Academic',
                    'Cluster of Electives' => 'Science, Technology, Engineering, and Mathematics (STEM)'
                ]),
                'status' => 'Done',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Kiosk Enrollment - Mark as Officially Enrolled milestone
            DB::table('kiosk_enrollments')->insert([
                'student_id' => $studentId,
                'student_lrn' => $lrn,
                'academic_status' => 'Officially Enrolled', // This is the MILESTONE status
                'grade_level' => 'Grade 11',
                'track' => 'Academic',
                'cluster' => $faker->randomElement(['STEM', 'ASSH', 'BE']),
                'started_at' => now()->subMinutes(25),
                'completed_at' => now()->subMinutes(15),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign documents based on the specific KIND of student
            $docList = $tracks[$kind];
            foreach ($docList as $docIndex => $docName) {
                $imgPath = !empty($sampleImages) 
                    ? $sampleImages[($index * 4 + $docIndex) % count($sampleImages)] 
                    : 'scans/sample_placeholder.jpg';
                
                DB::table('scans')->insert([
                    'user_id' => $user->id,
                    'document_type' => $docName,
                    'file_path' => $imgPath,
                    'lrn' => (str_contains($docName, 'Report') || str_contains($docName, 'ALS')) ? $lrn : null,
                    'status' => 'verified',
                    'remarks' => 'Verified by System',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
