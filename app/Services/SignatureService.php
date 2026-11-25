<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignatureService
{
    /**
     * Save a base64 PNG data URL as the user's stored signature if provided,
     * or return the existing signature path on the user's profile.
     */
    public function resolveOrSave(User $user, ?string $base64Signature): ?string
    {
        if ($base64Signature && str_starts_with($base64Signature, 'data:image/')) {
            $parts = explode(',', $base64Signature, 2);
            if (count($parts) === 2) {
                $binary = base64_decode($parts[1], true);
                if ($binary !== false) {
                    $filename = 'signatures/user_'.$user->id.'_'.Str::random(10).'.png';
                    Storage::disk('public')->put($filename, $binary);

                    if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
                        Storage::disk('public')->delete($user->signature_path);
                    }

                    $user->update(['signature_path' => $filename]);

                    return $filename;
                }
            }
        }

        return $user->signature_path;
    }
}
