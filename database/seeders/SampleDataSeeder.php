<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Job;
use App\Models\ProfileAdmin;
use App\Models\ProfilEmployer;
use App\Models\ProfilJobseeker;
use App\Models\SavedJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Sample/Demo data seeder for the JobBoard.
 *
 * Generates a rich, realistic dataset on top of DatabaseSeeder:
 *   - 2 additional admin users
 *   - 10 additional employers (varied sectors)
 *   - 30 additional jobs (varied categories, contracts, locations)
 *   - 25 additional job seekers (varied skills, education, experience)
 *   - 60 additional applications (varied statuses, dates, cover letters)
 *   - 25 saved-job bookmarks
 *
 * Run AFTER the base DatabaseSeeder (or after `migrate:fresh --seed`):
 *
 *   php artisan db:seed --class=SampleDataSeeder
 *
 * All accounts use password `password`. Emails are scoped to a unique
 * prefix (`sample.*`) so the seeder can be re-run on top of the base
 * dataset without colliding on the unique index.
 */
class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $admins      = $this->seedAdmins();
            $employers   = $this->seedEmployers();
            $jobs        = $this->seedJobs($employers);
            $jobseekers  = $this->seedJobSeekers();
            $this->seedApplications($jobseekers, $jobs);
            $this->seedSavedJobs($jobseekers, $jobs);
        });
    }

    // ---------------------------------------------------------------------
    // Admins
    // ---------------------------------------------------------------------

    private function seedAdmins(): array
    {
        $rows = [
            ['name' => 'Site Manager',  'email' => 'sample.admin.manager@jobboard.com'],
            ['name' => 'Support Lead',  'email' => 'sample.admin.support@jobboard.com'],
        ];

        $created = [];
        foreach ($rows as $r) {
            if (User::where('email', $r['email'])->exists()) {
                continue;
            }
            $user = User::create([
                'name'     => $r['name'],
                'email'    => $r['email'],
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ]);
            $profile = ProfileAdmin::create([
                'id_utilisateur' => $user->id,
                'name'           => $r['name'],
                'email'          => $r['email'],
            ]);
            $user->update(['profile_id' => $profile->id]);
            $created[] = $user;
        }

        return $created;
    }

    // ---------------------------------------------------------------------
    // Employers
    // ---------------------------------------------------------------------

    private function seedEmployers(): array
    {
        $rows = [
            ['name' => 'CloudNine Systems',    'sector' => 'Technology',  'addr' => '12 Skyline Plaza, Seattle, WA',         'desc' => 'Cloud-native infrastructure platform serving Fortune 500 customers.'],
            ['name' => 'GreenField Logistics', 'sector' => 'Logistics',   'addr' => '88 Harbor Rd, Long Beach, CA',          'desc' => 'Sustainable supply-chain and last-mile delivery network.'],
            ['name' => 'BrightMedia Agency',   'sector' => 'Media',       'addr' => '300 Broadway, New York, NY',             'desc' => 'Full-service digital media and content production house.'],
            ['name' => 'MediCore Labs',        'sector' => 'Healthcare',  'addr' => '5 Innovation Way, Boston, MA',          'desc' => 'Medical-device research and clinical software products.'],
            ['name' => 'NorthStar Bank',       'sector' => 'Finance',     'addr' => '1 Wall Street, New York, NY',           'desc' => 'Retail and corporate banking with a focus on digital channels.'],
            ['name' => 'PixelForge Games',     'sector' => 'Gaming',      'addr' => '42 Studio Park, Austin, TX',             'desc' => 'Indie game studio building cross-platform multiplayer titles.'],
            ['name' => 'EcoBuild Construction','sector' => 'Construction','addr' => '17 Industrial Ave, Denver, CO',          'desc' => 'Commercial green-building contractor with LEED-certified projects.'],
            ['name' => 'FreshBite Foods',      'sector' => 'FMCG',        'addr' => '9 Market St, Chicago, IL',               'desc' => 'Packaged-foods company specializing in plant-based products.'],
            ['name' => 'AeroNova Aerospace',   'sector' => 'Aerospace',   'addr' => '600 Flightline Dr, Huntsville, AL',     'desc' => 'Satellite systems and avionics for commercial and defense clients.'],
            ['name' => 'LearnPath Academy',    'sector' => 'Education',   'addr' => '24 Campus Way, Cambridge, MA',          'desc' => 'Online learning platform with self-paced and cohort-based courses.'],
        ];

        $created = [];
        foreach ($rows as $r) {
            $email = $this->employerEmail($r['name']);
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                $existingProfile = ProfilEmployer::find($existingUser->profile_id);
                if ($existingProfile) {
                    $created[] = $existingProfile;
                }
                continue;
            }

            $user = User::create([
                'name'     => $r['name'],
                'email'    => $email,
                'password' => Hash::make('password'),
                'role'     => 'employer',
            ]);
            $profile = ProfilEmployer::create([
                'id_utilisateur'   => $user->id,
                'nom_entreprise'   => $r['name'],
                'adresse'          => $r['addr'],
                'description'      => $r['desc'],
                'telephone'        => '+1-555-' . random_int(2000, 9999),
                'secteur_activite' => $r['sector'],
            ]);
            $user->update(['profile_id' => $profile->id]);
            $created[] = $profile;
        }

        return array_values(array_filter($created));
    }

    // ---------------------------------------------------------------------
    // Jobs
    // ---------------------------------------------------------------------

    private function seedJobs(array $employers): array
    {
        $types     = ['Full-time', 'Part-time', 'Remote', 'Contract', 'Freelance'];
        $contracts = ['CDI', 'CDD', 'Internship', 'Freelance'];
        $cats      = ['IT', 'Marketing', 'Finance', 'HR', 'Engineering', 'Design', 'Healthcare', 'Education', 'Logistics', 'Sales'];
        $locations = [
            'New York, NY', 'San Francisco, CA', 'Seattle, WA', 'Austin, TX',
            'Remote', 'Boston, MA', 'Chicago, IL', 'Los Angeles, CA',
            'Denver, CO', 'Miami, FL', 'Toronto, ON', 'Berlin, DE',
        ];

        $templates = [
            ['titre' => 'Senior Full-Stack Engineer',     'cat' => 'IT',          'sal' => 125000, 'type' => 'Full-time', 'desc' => 'Design, build, and operate production services end-to-end. You will own architecture decisions, mentor mid-level engineers, and partner with product on roadmap.'],
            ['titre' => 'Cloud Infrastructure Engineer', 'cat' => 'IT',          'sal' => 135000, 'type' => 'Full-time', 'desc' => 'Operate and evolve our multi-region Kubernetes platform. Strong Terraform and AWS experience required.'],
            ['titre' => 'Data Engineer',                 'cat' => 'IT',          'sal' => 105000, 'type' => 'Full-time', 'desc' => 'Build reliable data pipelines and dimensional models that power analytics and ML use cases.'],
            ['titre' => 'Machine Learning Engineer',     'cat' => 'IT',          'sal' => 145000, 'type' => 'Full-time', 'desc' => 'Train, evaluate, and deploy ML models for personalization and forecasting.'],
            ['titre' => 'Site Reliability Engineer',     'cat' => 'IT',          'sal' => 130000, 'type' => 'Full-time', 'desc' => 'Keep our production stack reliable, observable, and on-call ready.'],
            ['titre' => 'iOS Developer',                 'cat' => 'IT',          'sal' => 115000, 'type' => 'Contract',  'desc' => 'Ship high-quality iOS features in Swift / SwiftUI. App Store experience preferred.'],
            ['titre' => 'Frontend Engineer (React)',     'cat' => 'IT',          'sal' => 98000,  'type' => 'Remote',    'desc' => 'Build accessible, performant user interfaces in React and TypeScript.'],
            ['titre' => 'Backend Engineer (Go)',         'cat' => 'IT',          'sal' => 118000, 'type' => 'Full-time', 'desc' => 'Design event-driven services in Go. gRPC, Kafka, Postgres.'],
            ['titre' => 'Engineering Manager',           'cat' => 'IT',          'sal' => 165000, 'type' => 'Full-time', 'desc' => 'Lead a squad of 6-8 engineers across two product areas.'],
            ['titre' => 'Product Designer',              'cat' => 'Design',      'sal' => 102000, 'type' => 'Full-time', 'desc' => 'Lead end-to-end product design for a B2B SaaS suite. Figma expert.'],
            ['titre' => 'Brand Designer',                'cat' => 'Design',      'sal' => 88000,  'type' => 'Full-time', 'desc' => 'Evolve our visual identity across web, motion, and print.'],
            ['titre' => 'Motion Designer',               'cat' => 'Design',      'sal' => 78000,  'type' => 'Freelance', 'desc' => 'Produce short-form motion assets for product launches and social.'],
            ['titre' => 'UX Researcher',                 'cat' => 'Design',      'sal' => 92000,  'type' => 'Part-time', 'desc' => 'Run mixed-methods research to inform product strategy.'],
            ['titre' => 'Content Marketing Manager',     'cat' => 'Marketing',   'sal' => 86000,  'type' => 'Full-time', 'desc' => 'Own the editorial calendar, SEO content, and thought-leadership program.'],
            ['titre' => 'Performance Marketing Lead',    'cat' => 'Marketing',   'sal' => 110000, 'type' => 'Full-time', 'desc' => 'Run paid acquisition across Google, Meta, and LinkedIn.'],
            ['titre' => 'Social Media Manager',          'cat' => 'Marketing',   'sal' => 62000,  'type' => 'Part-time', 'desc' => 'Grow our community on LinkedIn, X, and Instagram.'],
            ['titre' => 'Email Marketing Specialist',    'cat' => 'Marketing',   'sal' => 70000,  'type' => 'Remote',    'desc' => 'Lifecycle email and CRM marketing in HubSpot / Customer.io.'],
            ['titre' => 'SEO Specialist',                'cat' => 'Marketing',   'sal' => 82000,  'type' => 'Full-time', 'desc' => 'Technical and on-page SEO for a content-rich product.'],
            ['titre' => 'Financial Controller',          'cat' => 'Finance',     'sal' => 145000, 'type' => 'Full-time', 'desc' => 'Lead monthly close, audit, and FP&A processes. CPA preferred.'],
            ['titre' => 'Senior Accountant',             'cat' => 'Finance',     'sal' => 88000,  'type' => 'Full-time', 'desc' => 'Manage general ledger, reconciliations, and tax filings.'],
            ['titre' => 'Treasury Analyst',              'cat' => 'Finance',     'sal' => 95000,  'type' => 'Full-time', 'desc' => 'Cash positioning, FX exposure, and short-term investments.'],
            ['titre' => 'Internal Audit Manager',        'cat' => 'Finance',     'sal' => 120000, 'type' => 'Full-time', 'desc' => 'Plan and execute risk-based internal audits.'],
            ['titre' => 'Talent Acquisition Partner',   'cat' => 'HR',          'sal' => 85000,  'type' => 'Full-time', 'desc' => 'Run full-cycle recruiting for engineering and product roles.'],
            ['titre' => 'People Operations Manager',     'cat' => 'HR',          'sal' => 105000, 'type' => 'Full-time', 'desc' => 'Build the employee experience: onboarding, engagement, performance.'],
            ['titre' => 'Compensation & Benefits Lead',  'cat' => 'HR',          'sal' => 115000, 'type' => 'Full-time', 'desc' => 'Design and benchmark total-rewards programs globally.'],
            ['titre' => 'Mechanical Engineer',           'cat' => 'Engineering', 'sal' => 92000,  'type' => 'Full-time', 'desc' => 'Design mechanical subsystems for our next-generation product line. SolidWorks.'],
            ['titre' => 'Civil Engineer',                'cat' => 'Engineering', 'sal' => 88000,  'type' => 'Full-time', 'desc' => 'Site supervision and structural design for commercial builds.'],
            ['titre' => 'Clinical Data Analyst',         'cat' => 'Healthcare',  'sal' => 84000,  'type' => 'Full-time', 'desc' => 'Analyze clinical trial datasets and prepare regulatory reports.'],
            ['titre' => 'Pharmacovigilance Specialist',  'cat' => 'Healthcare',  'sal' => 98000,  'type' => 'Full-time', 'desc' => 'Monitor adverse event reports and signal detection.'],
            ['titre' => 'Curriculum Designer',           'cat' => 'Education',   'sal' => 72000,  'type' => 'Remote',    'desc' => 'Build engaging self-paced curricula for adult learners.'],
            ['titre' => 'Supply Chain Analyst',          'cat' => 'Logistics',   'sal' => 78000,  'type' => 'Full-time', 'desc' => 'Forecast demand and optimize inventory across 4 DCs.'],
            ['titre' => 'Warehouse Operations Lead',     'cat' => 'Logistics',   'sal' => 68000,  'type' => 'Full-time', 'desc' => 'Lead a 40-person shift in a high-volume fulfillment center.'],
            ['titre' => 'Account Executive (SMB)',       'cat' => 'Sales',       'sal' => 95000,  'type' => 'Full-time', 'desc' => 'Own a book of SMB customers, drive expansion and renewals.'],
            ['titre' => 'Customer Success Manager',      'cat' => 'Sales',       'sal' => 88000,  'type' => 'Full-time', 'desc' => 'Drive adoption and renewals for a portfolio of mid-market accounts.'],
            ['titre' => 'Sales Development Rep',         'cat' => 'Sales',       'sal' => 55000,  'type' => 'Full-time', 'desc' => 'Outbound prospecting into target accounts.'],
        ];

        $created = [];
        foreach ($templates as $i => $tpl) {
            $employer = $employers[$i % count($employers)];

            // Skip if we already created a job with this title for this employer.
            $exists = Job::where('titre', $tpl['titre'])
                ->where('id_employeur', $employer->id)
                ->exists();
            if ($exists) {
                $created[] = Job::where('titre', $tpl['titre'])
                    ->where('id_employeur', $employer->id)
                    ->first();
                continue;
            }

            $created[] = Job::create([
                'id_employeur'     => $employer->id,
                'titre'            => $tpl['titre'],
                'description'      => $tpl['desc'],
                'location'         => $locations[array_rand($locations)],
                'job_type'         => $tpl['type'],
                'salaire'          => $tpl['sal'],
                'categorie'        => $tpl['cat'],
                'type_contrat'     => $contracts[array_rand($contracts)],
                'date_publication' => Carbon::now()->subDays(random_int(0, 45))->toDateString(),
                'company'          => $employer->nom_entreprise,
            ]);
        }

        return array_values(array_filter($created));
    }

    // ---------------------------------------------------------------------
    // Job Seekers
    // ---------------------------------------------------------------------

    private function seedJobSeekers(): array
    {
        $rows = [
            ['name' => 'Sophie Martin',     'email' => 'sample.sophie.martin@example.com',     'skills' => 'React, TypeScript, Next.js, GraphQL',                       'exp' => '6 years as Senior Frontend Engineer at Spotify',         'edu' => "Master's Degree in Computer Science"],
            ['name' => 'Liam OConnor',      'email' => 'sample.liam.oconnor@example.com',       'skills' => 'Go, Rust, Kubernetes, PostgreSQL',                          'exp' => '4 years as Backend Engineer at Stripe',                   'edu' => "Bachelor's Degree in Software Engineering"],
            ['name' => 'Aisha Khan',        'email' => 'sample.aisha.khan@example.com',         'skills' => 'Python, PyTorch, NLP, MLflow',                              'exp' => '5 years as ML Engineer at DeepMind',                      'edu' => "PhD in Machine Learning"],
            ['name' => 'Mateo Rossi',       'email' => 'sample.mateo.rossi@example.com',        'skills' => 'Figma, Design Systems, Accessibility, User Research',         'exp' => '7 years as Principal Designer at Airbnb',                 'edu' => "Master's Degree in Interaction Design"],
            ['name' => 'Hannah Becker',     'email' => 'sample.hannah.becker@example.com',      'skills' => 'SEO, Content Strategy, HubSpot, Google Analytics',           'exp' => '5 years as Content Marketing Lead at HubSpot',             'edu' => "Bachelor's Degree in Communications"],
            ['name' => 'Noah Dubois',       'email' => 'sample.noah.dubois@example.com',        'skills' => 'AWS, Terraform, Docker, GitHub Actions',                     'exp' => '6 years as DevOps Engineer at Datadog',                    'edu' => "Bachelor's Degree in Computer Engineering"],
            ['name' => 'Olivia Schmidt',    'email' => 'sample.olivia.schmidt@example.com',     'skills' => 'SQL, dbt, Snowflake, Looker',                                'exp' => '3 years as Analytics Engineer at Lyft',                     'edu' => "Master's Degree in Data Science"],
            ['name' => 'Ethan Wright',      'email' => 'sample.ethan.wright@example.com',       'skills' => 'Java, Spring Boot, Kafka, Microservices',                    'exp' => '8 years as Staff Engineer at LinkedIn',                    'edu' => "Master's Degree in Computer Science"],
            ['name' => 'Mia Patel',         'email' => 'sample.mia.patel@example.com',          'skills' => 'Swift, SwiftUI, Combine, Core Data',                         'exp' => '4 years as iOS Engineer at Square',                        'edu' => "Bachelor's Degree in Mobile Development"],
            ['name' => 'Lucas Romano',      'email' => 'sample.lucas.romano@example.com',       'skills' => 'After Effects, Cinema 4D, Lottie, Webflow',                  'exp' => '3 years as Motion Designer at Nike',                        'edu' => "Bachelor's Degree in Graphic Design"],
            ['name' => 'Chloe Laurent',     'email' => 'sample.chloe.laurent@example.com',      'skills' => 'CFA, Financial Modeling, Bloomberg, Excel',                   'exp' => '5 years as Investment Analyst at Goldman Sachs',            'edu' => "Master's in Finance"],
            ['name' => 'Daniel Kim',        'email' => 'sample.daniel.kim@example.com',         'skills' => 'Coupa, NetSuite, GAAP, SOX',                                 'exp' => '6 years as Senior Accountant at Cisco',                     'edu' => "Bachelor's Degree in Accounting"],
            ['name' => 'Zara Ahmed',        'email' => 'sample.zara.ahmed@example.com',         'skills' => 'Recruiting, Greenhouse, LinkedIn Recruiter, Sourcing',       'exp' => '4 years as Senior Recruiter at Notion',                     'edu' => "Bachelor's Degree in Psychology"],
            ['name' => 'Tomas Novak',       'email' => 'sample.tomas.novak@example.com',        'skills' => 'People Analytics, Lattice, Workday, HRIS',                   'exp' => '7 years as People Operations Lead at Shopify',              'edu' => "Master's in HR Management"],
            ['name' => 'Isabella Conti',    'email' => 'sample.isabella.conti@example.com',     'skills' => 'AutoCAD, SolidWorks, ANSYS, GD&T',                           'exp' => '5 years as Mechanical Engineer at Tesla',                    'edu' => "Master's in Mechanical Engineering"],
            ['name' => 'Yusuf Demir',       'email' => 'sample.yusuf.demir@example.com',        'skills' => 'Lean, Six Sigma, SAP, Demand Planning',                      'exp' => '6 years as Supply Chain Analyst at Unilever',                'edu' => "Bachelor's in Industrial Engineering"],
            ['name' => 'Amelia Foster',     'email' => 'sample.amelia.foster@example.com',      'skills' => 'R, SAS, CDISC, Clinical Reporting',                          'exp' => '3 years as Clinical Data Analyst at Pfizer',                 'edu' => "Master's in Biostatistics"],
            ['name' => 'Hiroshi Tanaka',    'email' => 'sample.hiroshi.tanaka@example.com',     'skills' => 'Instructional Design, Articulate, Camtasia, LXD',             'exp' => '4 years as Senior Curriculum Designer at Coursera',          'edu' => "Master's in Education Technology"],
            ['name' => 'Priya Subramanian', 'email' => 'sample.priya.subramanian@example.com',  'skills' => 'Outbound, Cold Email, Apollo, Outreach',                      'exp' => '2 years as SDR at Outreach.io',                             'edu' => "Bachelor's in Business Administration"],
            ['name' => 'Marco Rinaldi',     'email' => 'sample.marco.rinaldi@example.com',      'skills' => 'MEDDIC, Salesforce, Gong, Enterprise Sales',                 'exp' => '6 years as Account Executive at Twilio',                    'edu' => "Bachelor's in Marketing"],
            ['name' => 'Elena Petrova',     'email' => 'sample.elena.petrova@example.com',      'skills' => 'Gainsight, Churn Analysis, QBR',                             'exp' => '5 years as CSM at Datadog',                                 'edu' => "Bachelor's in International Business"],
            ['name' => 'Adam Cohen',        'email' => 'sample.adam.cohen@example.com',         'skills' => 'PHP, Laravel, MySQL, Redis',                                 'exp' => '7 years as Senior PHP Developer at Automattic',              'edu' => "Bachelor's in Computer Science"],
            ['name' => 'Layla Hassan',      'email' => 'sample.layla.hassan@example.com',       'skills' => 'Vue.js, Nuxt, Pinia, Vitest',                                'exp' => '3 years as Frontend Engineer at GitLab',                    'edu' => "Bachelor's in Software Engineering"],
            ['name' => 'Felix Andersen',    'email' => 'sample.felix.andersen@example.com',     'skills' => 'React Native, Expo, Detox',                                  'exp' => '4 years as Mobile Engineer at Shopify',                     'edu' => "Bachelor's in Computer Engineering"],
            ['name' => 'Jasmine Park',      'email' => 'sample.jasmine.park@example.com',       'skills' => 'Data Visualization, D3.js, Tableau, Storytelling',            'exp' => '2 years as Data Visualization Specialist at The New York Times', 'edu' => "Bachelor's in Statistics"],
        ];

        $created = [];
        foreach ($rows as $r) {
            $existingUser = User::where('email', $r['email'])->first();
            if ($existingUser) {
                $existingProfile = ProfilJobseeker::find($existingUser->profile_id);
                if ($existingProfile) {
                    $created[] = $existingProfile;
                }
                continue;
            }

            $user = User::create([
                'name'     => $r['name'],
                'email'    => $r['email'],
                'password' => Hash::make('password'),
                'role'     => 'Job Seeker',
            ]);
            $profile = ProfilJobseeker::create([
                'id_utilisateur'      => $user->id,
                'fullName'            => $r['name'],
                'contact_information' => '+1-555-' . random_int(2000, 9999),
                'competences'         => $r['skills'],
                'experience'          => $r['exp'],
                'education'           => $r['edu'],
                'resume'              => null,
            ]);
            $user->update(['profile_id' => $profile->id]);
            $created[] = $profile;
        }

        return array_values(array_filter($created));
    }

    // ---------------------------------------------------------------------
    // Applications
    // ---------------------------------------------------------------------

    private function seedApplications(array $seekers, array $jobs): void
    {
        $statuses = ['pending', 'approved', 'rejected'];
        $coverLetters = [
            'I am excited to apply for this role. My background aligns closely with what you are looking for, and I would love the opportunity to contribute to your team.',
            'I have followed your company for some time and admire the work you do. With my experience, I am confident I can add immediate value to your organization.',
            'This position stands out to me because it combines my technical strengths with the domain I am most passionate about. I look forward to discussing my fit in more detail.',
            'After reviewing the job description, I see a strong match between my recent work and your needs. I would welcome the chance to speak with you about how I can contribute.',
            'I am reaching out to express my strong interest in this role. My previous projects demonstrate the skills you are looking for, and I would be thrilled to bring that experience to your team.',
            'As a professional who enjoys solving complex problems, this opportunity is a great fit. I would appreciate the chance to discuss how my background translates to your needs.',
        ];

        // Each seeker applies to 2-3 distinct jobs.
        foreach ($seekers as $seeker) {
            $jobCount = random_int(2, 3);
            $picks = (array) array_rand(array_flip(array_keys($jobs)), $jobCount);
            foreach ($picks as $pickKey) {
                $job = $jobs[$pickKey];

                $alreadyApplied = Application::where('id_jobseeker', $seeker->id)
                    ->where('id_job', $job->id)
                    ->exists();
                if ($alreadyApplied) {
                    continue;
                }

                $application = Application::create([
                    'id_jobseeker' => $seeker->id,
                    'id_job'       => $job->id,
                    'status'       => $statuses[array_rand($statuses)],
                    'resume'       => null,
                    'cover_letter' => $coverLetters[array_rand($coverLetters)],
                ]);

                // Spread created_at across the last 60 days so the dashboard
                // month-aggregation charts have realistic distribution. We
                // bypass the model fillable guard via the query builder.
                $when = Carbon::now()->subDays(random_int(0, 60));
                DB::table('applications')
                    ->where('id', $application->id)
                    ->update([
                        'created_at' => $when,
                        'updated_at' => $when,
                    ]);
            }
        }
    }

    // ---------------------------------------------------------------------
    // Saved jobs (bookmarks)
    // ---------------------------------------------------------------------

    private function seedSavedJobs(array $seekers, array $jobs): void
    {
        foreach ($seekers as $seeker) {
            // Pull the underlying user_id (saved_jobs joins to users).
            $userId = DB::table('profil_jobseekers')
                ->where('id', $seeker->id)
                ->value('id_utilisateur');
            if (! $userId) {
                continue;
            }

            $bookmarkCount = random_int(1, 3);
            $picks = (array) array_rand(array_flip(array_keys($jobs)), $bookmarkCount);
            foreach ($picks as $pickKey) {
                $job = $jobs[$pickKey];

                $exists = SavedJob::where('id_utilisateur', $userId)
                    ->where('job_id', $job->id)
                    ->exists();
                if ($exists) {
                    continue;
                }

                SavedJob::create([
                    'id_utilisateur' => $userId,
                    'job_id'         => $job->id,
                    'profile_id'     => $seeker->id,
                ]);
            }
        }
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function employerEmail(string $companyName): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '', $companyName));
        return "sample.{$slug}@jobboard-demo.com";
    }
}
