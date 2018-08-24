<?php

namespace App\Http\Controllers;

use App\AlmaRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HomeController extends Controller
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        return response()->view('welcome');
    }
}
