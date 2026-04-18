<?php

namespace App\Http\Controllers;

use App\Services\TideService;
use Illuminate\View\View;

class MareaController extends Controller
{
    public function __construct(private TideService $tideService) {}

    public function index(): View
    {
        $tide = $this->tideService->getData();

        return view('marea.index', compact('tide'));
    }
}
