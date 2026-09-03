<?php

namespace App\Http\Controllers;

class SellController extends Controller
{
    public function show($sell = null)
    {
        return redirect()->route('loan-management.dashboard');
    }
}
