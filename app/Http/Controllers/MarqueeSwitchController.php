<?php

namespace App\Http\Controllers;

use App\Models\Marquee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarqueeSwitchController extends Controller
{
    /**
     * Switch active marquee in session for multi-business owners and area managers.
     */
    public function switch(Request $request)
    {
        $request->validate([
            'marquee_id' => 'required|integer|exists:marquees,id',
        ]);

        $user = Auth::user();
        $targetMarqueeId = (int) $request->input('marquee_id');

        // Verify that the user has permission to access this marquee
        $accessibleMarquees = $user->getAccessibleMarquees()->pluck('id')->toArray();

        if ($user->isSuperAdmin() || in_array($targetMarqueeId, $accessibleMarquees)) {
            session(['active_marquee_id' => $targetMarqueeId]);

            return back()->with('success', 'Active business switched successfully.');
        }

        return back()->with('error', 'You do not have access to switch to this business.');
    }
}
