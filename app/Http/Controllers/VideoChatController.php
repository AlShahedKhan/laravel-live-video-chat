<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VideoChatController extends Controller
{
    public function index()
    {
        return view('video-chat');
    }
}
