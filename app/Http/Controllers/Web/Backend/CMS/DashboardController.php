<?php

namespace App\Http\Controllers\Web\Backend\CMS;

use App\Http\Controllers\Controller;
use App\Models\ParkingSpace;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $totalHost=User::where('role', 'host')->count();
        $totalParkingSpace=ParkingSpace::count();
        $data=compact('totalHost','totalParkingSpace');
        return view('backend.layouts.dashboard.index',$data);
    }

}
