<?php

namespace App\Http\Controllers;

use App\Services\SignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $user = Auth::user()->load('supervisor');

        return view('profile.index', compact('user'));
    }

    public function update(Request $request, SignatureService $signatureService): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'signature_data' => ['nullable', 'string'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'department' => $validated['department'] ?? $user->department,
            'position' => $validated['position'] ?? $user->position,
        ]);

        if (! empty($validated['signature_data'])) {
            $signatureService->resolveOrSave($user, $validated['signature_data']);
        }

        return back()->with('success', 'Profile & signature updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password changed successfully.');
    }
}
