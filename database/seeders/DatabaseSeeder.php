<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ProfileAdmin;
use App\Models\ProfilEmployer;
use App\Models\ProfilJobseeker;
use App\Models\Job;
use App\Models\Application;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@jobboard.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        $adminProfile = ProfileAdmin::create([
            'id_utilisateur' => $admin->id,
            'name' => 'Admin',
            'email' => 'admin@jobboard.com',
        ]);
        $admin->update(['profile_id' => $adminProfile->id]);

        // Employers + Jobs
        $employerData = [
            ['name' => 'TechCorp', 'email' => 'hr@techcorp.com', 'sector' => 'Technology', 'addr' => '123 Tech Ave, San Francisco, CA', 'desc' => 'Leading technology company specializing in cloud solutions and enterprise software. We build innovative products used by millions worldwide.'],
            ['name' => 'FinancePlus', 'email' => 'jobs@financeplus.com', 'sector' => 'Finance', 'addr' => '456 Wall St, New York, NY', 'desc' => 'Premier financial services firm offering wealth management, investment banking, and insurance solutions to clients globally.'],
            ['name' => 'DesignHub', 'email' => 'careers@designhub.com', 'sector' => 'Design', 'addr' => '789 Creative Blvd, Los Angeles, CA', 'desc' => 'Award-winning design agency creating stunning digital experiences. We specialize in UI/UX, branding, and motion graphics.'],
            ['name' => 'HealthFirst', 'email' => 'recruit@healthfirst.com', 'sector' => 'Healthcare', 'addr' => '321 Medical Dr, Houston, TX', 'desc' => 'Network of hospitals and clinics dedicated to providing quality healthcare. We employ thousands of medical professionals across the country.'],
            ['name' => 'EduSmart', 'email' => 'team@edusmart.com', 'sector' => 'Education', 'addr' => '555 Learning Ln, Boston, MA', 'desc' => 'Innovative EdTech company transforming education through technology. Our platform serves over 1 million students worldwide.'],
        ];

        $employerProfiles = [];
        foreach ($employerData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role' => 'employer',
            ]);
            $profile = ProfilEmployer::create([
                'id_utilisateur' => $user->id,
                'nom_entreprise' => $data['name'],
                'adresse' => $data['addr'],
                'description' => $data['desc'],
                'telephone' => '+1-555-' . rand(1000, 9999),
                'secteur_activite' => $data['sector'],
            ]);
            $user->update(['profile_id' => $profile->id]);
            $employerProfiles[] = $profile;
        }

        $jobTypes = ['Full-time', 'Part-time', 'Remote', 'Contract', 'Freelance'];
        $contracts = ['CDI', 'CDD', 'Internship', 'Freelance'];
        $categories = ['IT', 'Marketing', 'Finance', 'HR', 'Engineering', 'Design', 'Healthcare', 'Education'];

        $jobsList = [
            ['titre' => 'Senior Frontend Developer', 'loc' => 'San Francisco, CA', 'type' => 'Full-time', 'sal' => 95000, 'cat' => 'IT', 'cidx' => 0, 'desc' => 'We are looking for an experienced Frontend Developer to build responsive web applications using React and TypeScript. You will work closely with our design team to create pixel-perfect user interfaces.'],
            ['titre' => 'Backend Engineer', 'loc' => 'San Francisco, CA', 'type' => 'Full-time', 'sal' => 110000, 'cat' => 'IT', 'cidx' => 0, 'desc' => 'Join our backend team to design and implement scalable APIs and microservices. Experience with Node.js, PostgreSQL, and AWS required.'],
            ['titre' => 'Financial Analyst', 'loc' => 'New York, NY', 'type' => 'Full-time', 'sal' => 78000, 'cat' => 'Finance', 'cidx' => 1, 'desc' => 'Analyze financial data, prepare reports, and provide insights to support business decisions. CFA or MBA preferred.'],
            ['titre' => 'UI/UX Designer', 'loc' => 'Remote', 'type' => 'Freelance', 'sal' => 65000, 'cat' => 'Design', 'cidx' => 2, 'desc' => 'Create beautiful and intuitive interfaces for web and mobile applications. Proficiency in Figma and Adobe Creative Suite required.'],
            ['titre' => 'Graphic Designer', 'loc' => 'Los Angeles, CA', 'type' => 'Part-time', 'sal' => 45000, 'cat' => 'Design', 'cidx' => 2, 'desc' => 'Design marketing materials, social media graphics, and brand assets. Strong portfolio required.'],
            ['titre' => 'Registered Nurse', 'loc' => 'Houston, TX', 'type' => 'Full-time', 'sal' => 72000, 'cat' => 'Healthcare', 'cidx' => 3, 'desc' => 'Provide quality patient care in a fast-paced hospital environment. RN license and BLS certification required.'],
            ['titre' => 'Math Teacher', 'loc' => 'Boston, MA', 'type' => 'Full-time', 'sal' => 58000, 'cat' => 'Education', 'cidx' => 4, 'desc' => 'Inspire high school students to excel in mathematics. Teaching certification and 2+ years experience required.'],
            ['titre' => 'DevOps Engineer', 'loc' => 'Seattle, WA', 'type' => 'Full-time', 'sal' => 120000, 'cat' => 'IT', 'cidx' => 0, 'desc' => 'Manage CI/CD pipelines, infrastructure as code, and cloud deployments. Kubernetes and Terraform experience required.'],
            ['titre' => 'Marketing Manager', 'loc' => 'Miami, FL', 'type' => 'Full-time', 'sal' => 85000, 'cat' => 'Marketing', 'cidx' => 1, 'desc' => 'Lead marketing campaigns across digital channels. Experience with SEO, SEM, and social media marketing required.'],
            ['titre' => 'Mobile App Developer', 'loc' => 'Austin, TX', 'type' => 'Contract', 'sal' => 90000, 'cat' => 'IT', 'cidx' => 0, 'desc' => 'Develop cross-platform mobile applications using React Native. Experience with iOS and Android deployment required.'],
        ];

        $createdJobs = [];
        foreach ($jobsList as $idx => $j) {
            $job = Job::create([
                'id_employeur' => $employerProfiles[$j['cidx']]->id,
                'titre' => $j['titre'],
                'description' => $j['desc'],
                'location' => $j['loc'],
                'job_type' => $j['type'],
                'salaire' => $j['sal'],
                'categorie' => $j['cat'],
                'type_contrat' => $contracts[array_rand($contracts)],
                'date_publication' => date('Y-m-d', strtotime("-" . rand(1, 60) . " days")),
                'company' => $employerData[$j['cidx']]['name'],
            ]);
            $createdJobs[] = $job;
        }

        // Jobseekers
        $seekerData = [
            ['name' => 'Alice Johnson', 'email' => 'alice@example.com', 'skills' => 'React, Vue.js, Tailwind CSS, TypeScript', 'exp' => '5 years as Frontend Developer at Google', 'edu' => "Master's Degree in Computer Science"],
            ['name' => 'Bob Smith', 'email' => 'bob@example.com', 'skills' => 'Python, Django, AWS, Docker', 'exp' => '3 years as Backend Developer at Amazon', 'edu' => "Bachelor's Degree in Software Engineering"],
            ['name' => 'Carol Davis', 'email' => 'carol@example.com', 'skills' => 'Figma, Adobe XD, Sketch, User Research', 'exp' => '4 years as UX Designer at Apple', 'edu' => "Bachelor's Degree in Design"],
            ['name' => 'David Wilson', 'email' => 'david@example.com', 'skills' => 'Java, Spring Boot, Microservices, Kafka', 'exp' => '7 years as Senior Developer at Microsoft', 'edu' => "Master's Degree in Information Technology"],
            ['name' => 'Eva Martinez', 'email' => 'eva@example.com', 'skills' => 'Excel, SQL, Tableau, Power BI', 'exp' => '2 years as Data Analyst at JPMorgan', 'edu' => "Bachelor's Degree in Finance"],
        ];

        $seekerProfiles = [];
        foreach ($seekerData as $idx => $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role' => 'Job Seeker',
            ]);
            $profile = ProfilJobseeker::create([
                'id_utilisateur' => $user->id,
                'fullName' => $data['name'],
                'contact_information' => '+1-555-' . rand(1000, 9999),
                'competences' => $data['skills'],
                'experience' => $data['exp'],
                'education' => $data['edu'],
                'resume' => null,
            ]);
            $user->update(['profile_id' => $profile->id]);
            $seekerProfiles[] = $profile;
        }

        // Applications
        $statuses = ['pending', 'approved', 'rejected'];
        foreach ($seekerProfiles as $idx => $profile) {
            $job = $createdJobs[$idx % count($createdJobs)];
            $coverLetters = [
                'I am excited to apply for this position. With my background and skills, I believe I would be a great fit for your team.',
                'I have been following your company for years and would love to contribute to your continued success. My experience aligns perfectly with this role.',
                'As a passionate professional with relevant experience, I am confident I can make significant contributions to your team from day one.',
            ];
            Application::create([
                'id_jobseeker' => $profile->id,
                'id_job' => $job->id,
                'status' => $statuses[array_rand($statuses)],
                'resume' => null,
                'cover_letter' => $coverLetters[array_rand($coverLetters)],
            ]);
        }

        // Augment with a richer sample dataset (extra employers, jobs,
        // job seekers, applications, saved jobs). Safe to re-run.
        $this->call(SampleDataSeeder::class);
    }
}
