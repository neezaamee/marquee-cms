<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Hall;
use App\Models\Package;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function index()
    {
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        // Onboarding checklist status calculation
        $setupChecklist = [];
        $isSetupCompleted = true;

        if (!$user->isSuperAdmin()) {
            $marquee = $marqueeId ? \App\Models\Marquee::find($marqueeId) : null;
            if ($marquee) {
                $isSetupCompleted = (bool) $marquee->is_setup_completed;

                if (!$isSetupCompleted) {
                    $setupChecklist = $marquee->getOnboardingChecklist();
                    
                    if (!in_array(false, $setupChecklist, true)) {
                        $marquee->update(['is_setup_completed' => true]);
                        $isSetupCompleted = true;
                    }
                }
            } else {
                $isSetupCompleted = false;
                $setupChecklist = [
                    'marquee_info' => false,
                    'branch' => false,
                    'branch_config' => false,
                    'halls' => false,
                    'departments' => false,
                    'booking_masters' => false,
                    'menu_packages' => false,
                    'inventory' => false,
                    'finance' => false,
                ];
            }
        }

        $isSuperAdmin = $user->isSuperAdmin();

        return view('dashboard', compact(
            'isSuperAdmin',
            'isSetupCompleted',
            'setupChecklist'
        ));
    }
}
