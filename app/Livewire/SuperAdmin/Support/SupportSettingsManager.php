<?php

namespace App\Livewire\SuperAdmin\Support;

use App\Models\SupportSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SupportSettingsManager extends Component
{
    // General Support Desk Settings
    public $ticketPrefix = 'TKT';
    public $supportEmail = 'support@marquee-cms.com';
    public $supportPhone = '+92 300 1234567';
    public $autoCloseDays = 7;
    public $enableLiveChat = true;
    public $defaultAgentId = null;

    // Operating Business Hours
    public $businessDays = [];
    public $businessStart = '09:00';
    public $businessEnd = '18:00';
    public $businessTimezone = 'Asia/Karachi';

    // SLA Matrix (Hours)
    public $slaUrgentResponse = 1;
    public $slaUrgentResolution = 4;
    public $slaHighResponse = 2;
    public $slaHighResolution = 8;
    public $slaNormalResponse = 4;
    public $slaNormalResolution = 24;
    public $slaLowResponse = 8;
    public $slaLowResolution = 48;

    public $savedSuccess = false;

    public function mount()
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && (!$user->hasPermission('support.settings.manage') || $user->isBusinessOwner()))) {
            abort(403, 'Unauthorized access to Support Configuration.');
        }

        $this->loadSettings();
    }

    public function loadSettings()
    {
        $this->ticketPrefix = SupportSetting::get('ticket_prefix', 'TKT');
        $this->supportEmail = SupportSetting::get('support_email', 'support@marquee-cms.com');
        $this->supportPhone = SupportSetting::get('support_phone', '+92 300 1234567');
        $this->autoCloseDays = (int) SupportSetting::get('auto_close_days', 7);
        $this->enableLiveChat = (bool) SupportSetting::get('enable_live_chat', true);
        $this->defaultAgentId = SupportSetting::get('default_agent_id', null);

        $hours = SupportSetting::get('business_hours', [
            'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'start' => '09:00',
            'end' => '18:00',
            'timezone' => 'Asia/Karachi',
        ]);
        $this->businessDays = $hours['days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $this->businessStart = $hours['start'] ?? '09:00';
        $this->businessEnd = $hours['end'] ?? '18:00';
        $this->businessTimezone = $hours['timezone'] ?? 'Asia/Karachi';

        $sla = SupportSetting::get('sla_targets_hours', [
            'urgent' => ['response' => 1, 'resolution' => 4],
            'high' => ['response' => 2, 'resolution' => 8],
            'normal' => ['response' => 4, 'resolution' => 24],
            'low' => ['response' => 8, 'resolution' => 48],
        ]);
        $this->slaUrgentResponse = $sla['urgent']['response'] ?? 1;
        $this->slaUrgentResolution = $sla['urgent']['resolution'] ?? 4;
        $this->slaHighResponse = $sla['high']['response'] ?? 2;
        $this->slaHighResolution = $sla['high']['resolution'] ?? 8;
        $this->slaNormalResponse = $sla['normal']['response'] ?? 4;
        $this->slaNormalResolution = $sla['normal']['resolution'] ?? 24;
        $this->slaLowResponse = $sla['low']['response'] ?? 8;
        $this->slaLowResolution = $sla['low']['resolution'] ?? 48;
    }

    public function saveSettings()
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && (!$user->hasPermission('support.settings.manage') || $user->isBusinessOwner()))) {
            abort(403, 'Unauthorized to modify settings.');
        }

        $this->validate([
            'ticketPrefix' => 'required|string|max:10|regex:/^[A-Z0-9]+$/',
            'supportEmail' => 'required|email|max:150',
            'supportPhone' => 'nullable|string|max:50',
            'autoCloseDays' => 'required|integer|min:1|max:90',
            'businessDays' => 'required|array|min:1',
            'businessStart' => 'required|string',
            'businessEnd' => 'required|string',
            'businessTimezone' => 'required|string|max:50',
            'slaUrgentResponse' => 'required|integer|min:1|max:168',
            'slaUrgentResolution' => 'required|integer|min:1|max:336',
            'slaHighResponse' => 'required|integer|min:1|max:168',
            'slaHighResolution' => 'required|integer|min:1|max:336',
            'slaNormalResponse' => 'required|integer|min:1|max:168',
            'slaNormalResolution' => 'required|integer|min:1|max:336',
            'slaLowResponse' => 'required|integer|min:1|max:168',
            'slaLowResolution' => 'required|integer|min:1|max:336',
            'defaultAgentId' => 'nullable|exists:users,id',
        ]);

        SupportSetting::set('ticket_prefix', strtoupper($this->ticketPrefix), 'Prefix for support ticket numbers');
        SupportSetting::set('support_email', $this->supportEmail, 'Primary support team contact email');
        SupportSetting::set('support_phone', $this->supportPhone, 'Support hotline contact number');
        SupportSetting::set('auto_close_days', (int) $this->autoCloseDays, 'Days before resolved tickets automatically close');
        SupportSetting::set('enable_live_chat', (bool) $this->enableLiveChat, 'Enable real-time customer live chat widget');
        SupportSetting::set('default_agent_id', $this->defaultAgentId ? (int) $this->defaultAgentId : null, 'Default assignee for unassigned inbound tickets');

        SupportSetting::set('business_hours', [
            'days' => $this->businessDays,
            'start' => $this->businessStart,
            'end' => $this->businessEnd,
            'timezone' => $this->businessTimezone,
        ], 'Support desk operating hours');

        SupportSetting::set('sla_targets_hours', [
            'urgent' => [
                'response' => (int) $this->slaUrgentResponse,
                'resolution' => (int) $this->slaUrgentResolution,
            ],
            'high' => [
                'response' => (int) $this->slaHighResponse,
                'resolution' => (int) $this->slaHighResolution,
            ],
            'normal' => [
                'response' => (int) $this->slaNormalResponse,
                'resolution' => (int) $this->slaNormalResolution,
            ],
            'low' => [
                'response' => (int) $this->slaLowResponse,
                'resolution' => (int) $this->slaLowResolution,
            ],
        ], 'SLA targets by priority in hours');

        $this->savedSuccess = true;
        session()->flash('success', 'Support configuration and SLA parameters updated successfully.');
    }

    public function render()
    {
        $agents = User::whereHas('role', fn($r) => $r->whereIn('name', ['super_admin', 'support_agent', 'admin']))
            ->orWhereNull('marquee_id')
            ->orderBy('name')
            ->get();

        $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return view('livewire.super-admin.support.support-settings-manager', [
            'agents' => $agents,
            'weekDays' => $weekDays,
        ])->layout('layouts.admin', ['title' => 'Support Desk & SLA Settings']);
    }
}
