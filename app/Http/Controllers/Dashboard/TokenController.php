<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TokenController extends Controller
{
    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    public function index(Request $request): View
    {
        return view('tokens.index', ['tokens' => $this->user($request)->tokens()->latest('id')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['in:read,write'],
        ]);
        $token = $this->user($request)->createToken($data['name'], array_values(array_unique($data['abilities'])));

        // Shown once: only a hash of the token is stored.
        return back()->with('new_token', $token->plainTextToken)->with('status', 'Token created. Copy it now: it won\'t be shown again.');
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $this->user($request)->tokens()->whereKey($token)->delete();

        return back()->with('status', 'Token revoked. Apps using it stop working immediately.');
    }
}
