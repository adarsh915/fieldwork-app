<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Services\DataScope;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function index()
    {
        $query = Visit::with(['party', 'user']);
        DataScope::apply($query, 'visits');

        $visits = $query->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.visits.index', compact('visits'));
    }
}
