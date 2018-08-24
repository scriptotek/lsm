<?php

namespace App\Http\Controllers;

use App\AlmaRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StatsController extends Controller
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        $data = app('db')->select("SELECT time, action, msecs, data FROM timing");

        return response()->json($data);
    }
}
