<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function cookies(): View
    {
        return view('pages.cookies');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }

    public function editorial(): View
    {
        return view('pages.editorial');
    }
}
