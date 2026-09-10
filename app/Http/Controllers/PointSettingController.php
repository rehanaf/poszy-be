<?php

namespace App\Http\Controllers;

use App\Models\PointSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PointSettingController extends Controller
{
    private function getSetting()
    {
        return PointSetting::firstOrCreate(['id' => 1]);
    }

    public function index()
    {
        return response()->json($this->getSetting(), 200);
    }

    public function update(Request $request)
    {
        try {
            $setting = $this->getSetting();

            $request->validate([
                'earn_min_amount' => ['nullable', 'integer', 'min:0'],
                'earn_points' => ['nullable', 'integer', 'min:0'],
                'earn_multiple' => ['nullable', 'boolean'],
                'exchange_points' => ['nullable', 'integer', 'min:0'],
                'exchange_discount_value' => ['nullable', 'numeric', 'min:0'],
                'exchange_discount_type' => ['nullable', 'string', 'in:percent,nominal'],
            ]);

            $setting->update([
                'earn_min_amount' => $request->input('earn_min_amount', $setting->earn_min_amount),
                'earn_points' => $request->input('earn_points', $setting->earn_points),
                'earn_multiple' => $request->boolean('earn_multiple', $setting->earn_multiple),
                'exchange_points' => $request->input('exchange_points', $setting->exchange_points),
                'exchange_discount_value' => $request->input('exchange_discount_value', $setting->exchange_discount_value),
                'exchange_discount_type' => $request->input('exchange_discount_type', $setting->exchange_discount_type),
            ]);

            return response()->json([
                'message' => 'Point settings updated successfully.',
                'setting' => $setting
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating point settings.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}