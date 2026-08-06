<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        if (session('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('landing');
    }
}
