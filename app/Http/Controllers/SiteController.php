<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SiteController extends Controller
{
    public function getHome()
    {
        return Inertia::render('Home', []);
    }

    public function getTinTuc()
    {
        return Inertia::render('TinTuc', []);
    }
}
