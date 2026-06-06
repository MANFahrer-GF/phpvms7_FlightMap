<?php

namespace Modules\FlightMap\Http\Controllers\Frontend;

use App\Contracts\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index(Request $request)
    {
        return view('flightmap::index', [
            'map_height' => 780,
        ]);
    }
}
