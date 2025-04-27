<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TESTCONTROLLER extends Controller
{
    public function index(){
         return Inertia::render('content/test');
    }
}
