<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Booking;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;

class ProfileController extends Controller
{
    /**
     * Show the authenticated user's profile.
     */
    public function show()
    {
        $user = Auth::user();
        $user->load(['role', 'branch', 'marquee', 'employee']);

        // Calculate statistics
        $bookingsCount = Booking::where('created_by', $user->id)->count();
        $bookingsValue = Booking::where('created_by', $user->id)->sum('grand_total');
        $activityCount = ActivityLog::where('user_id', $user->id)->count();

        // Paginate activity logs for this user
        $activityLogs = ActivityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('profile.show', compact(
            'user',
            'bookingsCount',
            'bookingsValue',
            'activityCount',
            'activityLogs'
        ));
    }

    /**
     * Update the authenticated user's profile details.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => ['nullable', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $photoPath = $user->profile_photo;

        // Handle photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $photoPath = $request->file('profile_photo')->store('profile_photos', 'public');
        }

        // If user is linked to an employee, update employee record as well
        if ($user->employee) {
            $employeeData = [
                'name' => $validated['name'],
                'mobile_number' => $validated['phone'],
            ];

            if ($request->hasFile('profile_photo')) {
                // If employee has a photo, clean it up
                if ($user->employee->photo) {
                    Storage::disk('public')->delete($user->employee->photo);
                }
                $employeeData['photo'] = $photoPath;
            }

            $user->employee->update($employeeData);
        }

        // Update user
        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'profile_photo' => $photoPath,
        ]);

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.show')->with('success', 'Password changed successfully.');
    }
}
