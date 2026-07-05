<?php

namespace App\Http\Controllers\Jobseeker;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\SavedJob;
use Illuminate\Http\Request;

class SavedJobsController extends Controller
{
    public function index(){

        // Get the authenticated user's profile
        $profile = auth()->user()->profile;

        if (!$profile) {
            return redirect()->route('jobseeker.profile.create')->with('error', 'Please create your profile first.');
        }

        // Retrieve saved jobs for the user
        $savedjobs = SavedJob::with('job')->where('profile_id', $profile->id)->get();

        // Pass saved jobs to the view
        return view('Jobseeker.savedjobs',compact('savedjobs'));
    }
}
