<?php

namespace Willypelz\LogPlatform\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class LogPlatformController extends Controller
{
    /**
     * Display the log platform UI.
     */
    public function index(): View
    {
        return view('log-platform::index');
    }
}

