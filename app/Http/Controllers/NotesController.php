<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class NotesController extends Controller
{
    /**
     * The post-it board of small things learned along the way.
     */
    public function index(): View
    {
        return view('site.notes.index');
    }
}
