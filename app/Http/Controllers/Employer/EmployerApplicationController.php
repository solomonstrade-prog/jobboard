<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class EmployerApplicationController extends Controller
{
    
    public function index(){

        $employerProfile = auth()->user()->profile;
        if (!$employerProfile) {
            return redirect()->route('employer.profile.create')->with('error', 'Please create your employer profile first.');
        }
        $employerId = $employerProfile->id; // ID de l'employeur connecté

        $applications =Application::whereHas('job',function ($query) use ($employerId){
            $query->where('id_employeur',$employerId); // Filtrer les emplois de cet employeur
        })->with('job','profilJobseeker')->get();

        return view('Employer.applicationjobs',compact('applications'));
    }




    public function updateStatus(Request $request, Application $application)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected', // Ensure valid statuses
        ]);
    
    
        // Assign the new status and save the application
        $application->status = $validated['status'];
        $application->save();
    
        // Optionally, add a success message
        return redirect()->back()->with('success', 'Status updated successfully.');
    }


    public function show($id){
        $application = Application::with(['job','profilJobseeker'])->find($id);

        return view('Employer.showapplication',compact('application'));
    }
    



    public function destroy($id){
        $application = Application::findOrFail($id);

        $application->delete();

        return redirect()->route('employer.applications.index')->with('success', 'Application deleted successfully.');
    }
    
















}
