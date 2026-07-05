<?php

namespace App\Http\Controllers\Jobseeker;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class JobseekerApplicationController extends Controller
{
    public function index(){

        // Get the authenticated user's profile
        $profile = auth()->user()->profile;

        if (!$profile) {
            return redirect()->route('jobseeker.profile.create')->with('error', 'Please create your profile first.');
        }

        // Retrieve applications for the user
        $applications = Application::with('job')->where('id_jobseeker', $profile->id)->get();

        return view("Jobseeker.applicationjobs",compact('applications'));
    }


    public function show($id){
        $application=Application::with('job')->find($id);

        return view("Jobseeker.showapplication",compact("application"));
    }
}
