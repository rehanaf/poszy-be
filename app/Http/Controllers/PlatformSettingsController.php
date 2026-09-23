<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PlatformSettingsController extends Controller
{
    /**
     * Konversi path logo (id) menjadi URL publik ber-CORS via /api/logo.
     */
    private function logoUrl(mixed $path): ?string
    {
        if (! $path) {
            return null;
        }

        return url('/api/logo/' . ltrim((string) $path, '/'));
    }

    /**
     * Gabungkan pengaturan platform menjadi satu array.
     */
    private function settings(): array
    {
        return [
            'powered_by_enabled' => (bool) PlatformSetting::get('powered_by_enabled', true),
            'powered_by_text' => (string) PlatformSetting::get('powered_by_text', 'Powered by SemestaPOS'),
            'powered_by_logo' => PlatformSetting::get('powered_by_logo'),
            'powered_by_logo_url' => $this->logoUrl(PlatformSetting::get('powered_by_logo')),
        ];
    }

    /**
     * Pengaturan publik (tanpa auth) untuk dirender di struk.
     */
    public function publicShow()
    {
        return response()->json($this->settings(), 200);
    }

    /**
     * Pengaturan global — hanya superadmin.
     */
    public function show(Request $request)
    {
        if (! $request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($this->settings(), 200);
    }

    /**
     * Perbarui pengaturan global — hanya superadmin.
     */
    public function update(Request $request)
    {
        if (! $request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        try {
            $validated = $request->validate([
                'powered_by_enabled' => ['nullable', 'boolean'],
                'powered_by_text' => ['nullable', 'string', 'max:255'],
            ]);

            if (array_key_exists('powered_by_enabled', $validated)) {
                PlatformSetting::set('powered_by_enabled', (bool) $validated['powered_by_enabled']);
            }
            if (array_key_exists('powered_by_text', $validated)) {
                PlatformSetting::set('powered_by_text', $validated['powered_by_text']);
            }

            return response()->json([
                'message' => 'Pengaturan global berhasil diperbarui.',
                'settings' => $this->settings(),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload logo "Powered by" — hanya superadmin.
     */
    public function uploadLogo(Request $request)
    {
        if (! $request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        try {
            $request->validate([
                'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            ]);

            $path = $request->file('logo')->store('logos/platform', 'public');
            PlatformSetting::set('powered_by_logo', $path);

            return response()->json([
                'message' => 'Logo berhasil diperbarui.',
                'powered_by_logo' => $path,
                'powered_by_logo_url' => $this->logoUrl($path),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Hapus logo "Powered by" — hanya superadmin.
     */
    public function deleteLogo(Request $request)
    {
        if (! $request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        try {
            $path = PlatformSetting::get('powered_by_logo');
            if ($path) {
                Storage::disk('public')->delete((string) $path);
            }
            PlatformSetting::set('powered_by_logo', null);

            return response()->json([
                'message' => 'Logo berhasil dihapus.',
                'powered_by_logo' => null,
                'powered_by_logo_url' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }
}