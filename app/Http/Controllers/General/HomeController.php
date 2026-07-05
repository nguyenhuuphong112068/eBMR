<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function showHomeForm()
    {
        session()->put(['title' => 'STELLAPHARM - EBR SYSTEM']);

        return view('pages.general.home');
    }
}
