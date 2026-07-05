<?php

use App\Http\Controllers\Admin\AdminApplicationController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminJobController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminProfilEmployerController;
use App\Http\Controllers\Admin\AdminProfilJobseekerController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Employer\EmployerApplicationController;
use App\Http\Controllers\Employer\EmployerCondidateController;
use App\Http\Controllers\Employer\EmployerJobController;
use App\Http\Controllers\Employer\ProfilEmployerController;
use App\Http\Controllers\Jobseeker\JobseekerApplicationController;
use App\Http\Controllers\Jobseeker\JobseekerJobController;
use App\Http\Controllers\Jobseeker\ProfileJobseekerController;
use App\Http\Controllers\Jobseeker\SavedJobsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TestEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/tt',function () {
    return "hello";
})->middleware(['auth','role:Job Seeker']);


// Admin Routes
Route::middleware(['auth','role:admin'])->group(function () {
    // Dashboard for Admin
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('admin.dashboard');

    Route::prefix('profileAdmin')->group( function (){
        Route::get('/create', [AdminProfileController::class, 'create'])->name('admin.profile.create');
        Route::post('/', [AdminProfileController::class, 'store'])->name('admin.profile.store');
    });



    // Job Management for Admin
    Route::prefix('jobs')->group(function () {
        Route::get('/', [AdminJobController::class, 'index'])->name('jobs.index');
        Route::delete('/{id}', [AdminJobController::class, 'destroy'])->name('jobs.destroy');
        Route::get('/search', [AdminJobController::class, 'search'])->name('jobs.search');
    });


    // Job Seeker Management
    Route::prefix('jobseekers')->group(function () {
        Route::get('/', [AdminProfilJobseekerController::class, 'index'])->name('jobseeker.index');
        Route::delete('/{id}', [AdminProfilJobseekerController::class, 'destroy'])->name('jobseeker.destroy');
        Route::get('/search', [AdminProfilJobseekerController::class, 'search'])->name('jobseeker.search');
    });


    // Employer Management
    Route::prefix('employers')->group(function () {
        Route::get('/', [AdminProfilEmployerController::class, 'index'])->name('employers.index');
        Route::get('/create', [AdminProfilEmployerController::class, 'create'])->name('employers.create');
        Route::post('/', [AdminProfilEmployerController::class, 'store'])->name('employers.store');
        Route::get('/{id}/edit', [AdminProfilEmployerController::class, 'edit'])->name('employers.edit');
        Route::put('/{id}', [AdminProfilEmployerController::class, 'update'])->name('employers.update');
        Route::delete('/{id}', [AdminProfilEmployerController::class, 'destroy'])->name('employers.destroy');
        Route::get('/search', [AdminProfilEmployerController::class, 'search'])->name('employers.search');
    });


    // Application Management for Admin
    Route::prefix('applications')->group(function () {
        Route::get('/', [AdminApplicationController::class, 'index'])->name('applications.index');
        Route::get('/search', [AdminApplicationController::class, 'search'])->name('applications.search');
        Route::get('/{id}', [AdminApplicationController::class, 'show'])->name('applications.show');
        Route::delete('/{id}', [AdminApplicationController::class, 'destroy'])->name('applications.destroy');
    });

    // Update application status (admin). Kept at top-level singular URL —
    // the Breeze /admin tests expect PUT /application/{id}/status.
    Route::put('/application/{application}/status', [AdminApplicationController::class, 'updateStatus'])
        ->name('updateStatus');

    //logout Employer dashboard
    Route::get('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])->name('admin.logout');


});




    // Public job browsing — accessible without login.
    // The view itself swaps in login CTAs for guests (see Jobseeker/jobs-public.blade.php).
    Route::get('/jobseeker/jobs', [JobseekerJobController::class, 'index'])->name('jobseeker.jobs.index');
    Route::get('/jobseeker/search', [JobseekerJobController::class, 'search'])->name('jobseeker.jobs.search');

    // Job Seeker Routes
    Route::middleware(['auth', 'role:Job Seeker'])->group(function () {


        // Dashboard for Job Seeker
        Route::get('/jobseeker/dashboard', [JobseekerJobController::class, 'dashboardJobseker'])->name('jobseeker.dashboard');

        // Applying and Saving Sobs
        Route::post('/jobs/{job}/apply', [JobseekerJobController::class, 'apply'])->name('jobs.apply');
        Route::post('/jobs/{job}/save', [JobseekerJobController::class, 'saveJob'])->name('jobs.save');
        Route::get('/saved-jobs', [SavedJobsController::class, 'index'])->name('savedJobs.index');



        // Job Applications Management
        Route::get('/jobseeker/applications', [JobseekerApplicationController::class, 'index'])->name('jobseeker.applications.index');
        Route::get('/jobseeker/applications/{id}', [JobseekerApplicationController::class, 'show'])->name('jobseeker.applications.show');
        Route::delete('/jobseeker/applications/{id}', [JobseekerApplicationController::class, 'destroy'])->name('jobseeker.applications.destroy');


        // Profile Management Routes
        Route::controller(ProfileController::class)->group(function () {
            Route::get('/jobseeker/profile/create', 'create')->name('jobseeker.profile.create');
            Route::post('/jobseeker/profile', 'store')->name('profile.store');
            Route::delete('/profil', 'destroy')->name('profile.destroy');
        });

        // Edit and Update Job Seeker Profile
        Route::get('/profilejobseeker/edit', [ProfileJobseekerController::class, 'edit'])->name('profile.edit');
        Route::put('/profilejobseeker/update', [ProfileJobseekerController::class, 'update'])->name('profile.update');

        // Logout Jobseeker Route
        Route::get('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('jobseeker.logout');


    });




    // Employer Routes
    Route::middleware(['auth', 'role:employer'])->group(function () {
        // Dashboard for Employer
        Route::get('/employer/dashboard', [EmployerJobController::class, 'dashboardEmployer'])->name('employer.dashboard');

        // Manage Jobs
        Route::get('/employer/jobs', [EmployerJobController::class, 'index'])->name('employer.jobs.index'); // List all jobs
        Route::get('/employer/jobs/create', [EmployerJobController::class, 'create'])->name('employer.jobs.create'); // Show form to create a new job
        Route::post('/employer/jobs', [EmployerJobController::class, 'store'])->name('employer.jobs.store'); // Store a new job
        Route::get('/employer/jobs/{id}/edit', [EmployerJobController::class, 'edit'])->name('employer.jobs.edit'); // Show form to edit a job
        Route::put('/employer/jobs/{id}', [EmployerJobController::class, 'update'])->name('employer.jobs.update'); // Update an existing job
        Route::delete('/employer/jobs/{id}', [EmployerJobController::class, 'destroy'])->name('employer.jobs.destroy'); // Delete a job
        Route::get('/search', [EmployerJobController::class, 'search'])->name('employer.jobs.search'); // Search for jobs

        // View Applications for Jobs Posted by Employer
        Route::get('/employer/applications', [EmployerApplicationController::class, 'index'])->name('employer.applications.index'); // List all applications
        Route::get('/employer/applications/{id}', [EmployerApplicationController::class, 'show'])->name('employer.applications.show'); // View a specific application
        Route::delete('/employer/applications/{id}', [EmployerApplicationController::class, 'destroy'])->name('employer.applications.destroy'); // Delete an application

        // Update the status of an application
        Route::put('/applications/{application}/status', [EmployerApplicationController::class, 'updateStatus'])->name('applications.updateStatus');

        // Manage Candidates
        Route::get('/employer/candidates', [EmployerCondidateController::class, 'candidates'])->name('employer.candidates'); // List all candidates
        Route::get('/employer/candidates/{id}', [EmployerCondidateController::class, 'show'])->name('candidates.show'); // View details of a specific candidate

        // Employer Profile Management
        Route::get('/profileEmployer/create', [ProfilEmployerController::class, 'create'])->name('employer.profile.create');
        Route::post('/profileEmployer', [ProfilEmployerController::class, 'store'])->name('employer.profile.store');
        Route::get('/profileEmployer/edit', [ProfilEmployerController::class, 'edit'])->name('employer.profile.edit'); // Show form to edit employer profile
        Route::put('/profileEmployer/update', [ProfilEmployerController::class, 'update'])->name('employer.profile.update'); // Update employer profile
        Route::delete('/profile',[ProfileController::class , 'destroy'])->name('employer.profile.destroy');



        //logout Employer dashboard
        Route::get('/employer/logout', [AuthenticatedSessionController::class, 'destroy'])->name('employer.logout');
    });


    Route::get('/test', [TestEmailController::class, 'testEmail']);


require __DIR__.'/auth.php';
