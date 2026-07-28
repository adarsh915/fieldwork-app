<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function index()
    {
        $visits = Visit::with(['party', 'user'])->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.visits.index', compact('visits'));
    }
}
