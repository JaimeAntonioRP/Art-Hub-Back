<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Certificate;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'obras'        => Artwork::count(),
            'artistas'     => Artist::count(),
            'certificados' => Certificate::count(),
            'usuarios'     => User::count(),
        ];

        $recentArtworks = Artwork::latest()->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'recentArtworks'));
    }
}
