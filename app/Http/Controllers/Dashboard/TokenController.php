<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TokenController extends Controller
{
    public function index(Request $request): View
    {
        return view('tokens.index', ['tokens' => $request->user()->tokens()->latest('id')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['in:read,write'],
        ]);
        $token = $request->user()->createToken($data['name'], array_values(array_unique($data['abilities'])));

        // Shown once: only a hash of the token is stored.
        return back()->with('new_token', $token->plainTextToken)->with('status', 'Token created. Copy it now: it won\'t be shown again.');
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return back()->with('status', 'Token revoked. Apps using it stop working immediately.');
    }
}
