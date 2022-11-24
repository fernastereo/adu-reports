<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard');
    }

    public function appointmentReport()
    {
        return Inertia::render('Reports/AppointmentReport');
    }

    public function syncData()
    {
        return Inertia::render('SyncData/SyncData');
    }
}