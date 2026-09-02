<?php
 
namespace App\Livewire;
 
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Package;
use App\Models\Slot;
use App\Models\ExtraService;
use App\Models\MenuItem;
use App\Services\AvailabilityService;
use App\Services\BookingPricingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BookingEdit extends Component
{
    public Booking $booking;
    public $marquee_id = null;

    // Branch scoping properties
    public $selectedBranchId = '';
    public $branchesList = [];
    public $isMultiBranchUser = false;
    public $canChangeBranch = false;

    // Form inputs
    public $selectedCustomerId = '';
    public $selectedEventTypeId = '';
    public $selectedHallId = '';
    public $selectedDate = '';

    public $checkType = 'slot'; // slot, custom
    public $selectedSlotId = '';
    public $customStart = '';
    public $customEnd = '';
    public $startTime = '';
    public $endTime = '';

    public $selectedPackageId = '';
    public $guestCount = 0;
    public $perPlatePrice = 0.00;
    public $hallCharges = 0.00;
    public $extraCharges = 0.00;
    public $discountAmount = 0.00;
    public $securityDeposit = 0.00;
    public $taxRate = 13.00;

    public $packageAmount = 0.00;
    public $subtotal = 0.00;
    public $taxAmount = 0.00;
    public $grandTotal = 0.00;

    // Add-ons properties
    public $addonsList = [];
    public $selectedAddons = []; // extra_service_id => ['selected' => bool, 'price' => float, 'quantity' => int, 'name' => string]

    // Menu Customization selection
    public $bookingMenuItems = []; // array of ['id' => int, 'item_name' => string, 'custom_note' => string, 'managed_by_host' => bool]
    public $selectedMenuItemToAdd = ''; // dropdown selection to add menu item
    public $menuItemsAutocomplete = []; // list of all menu items for the dropdown
    public $menuItemSearch = '';

    // Search and Multi-select states
    public $eventTypeSearch = '';
    public $hallSearch = '';
    public $selectedHallIds = [];
    public $filteredEventTypes = [];
    public $filteredHalls = [];

    // Rent / Sitting Plan only state
    public $noFood = false;

    // Privacy / Partition properties
    public $privacyRequired = false;
    public $privacyLadiesPercentage = '';
    public $privacyGentsPercentage = '';

    public function updatedPrivacyRequired($value)
    {
        if (!$value) {
            $this->privacyLadiesPercentage = '';
            $this->privacyGentsPercentage = '';
        }
    }

    // Guest confirmation fields
    public $tentativeGuests = 100;
    public $confirmedGuests = null;
    public $guestStatus = 'Tentative';

    public $specialInstructions = '';
    public $bookingStatus = '';
    public $paymentStatus = '';

    // Cache dropdown lists
    public $customersList = [];
    public $eventTypesList = [];
    public $hallsList = [];
    public $packagesList = [];
    public $availableSlotsList = [];

    // Availability Checks
    public $isAvailable = true;
    public $availabilityChecked = false;
    public $conflictDetails = null;

    public function mount(Booking $booking)
    {
        $this->booking = $booking;

        // Owner/Superadmin lock validation
        $user = auth()->user();
        $isOwner = $user && ($user->isSuperAdmin() || $user->isBusinessOwner());
        $isLocked = in_array($booking->booking_status, ['Completed', 'Cancelled']);

        if ($isLocked && !$isOwner) {
            session()->flash('error', 'Only owners or super admins can edit Completed or Cancelled bookings.');
            return redirect()->route('bookings.show', $booking->id);
        }

        abort_unless($user && $user->can('update', $booking), 403, 'Unauthorized access to edit this booking.');

        $this->selectedCustomerId = $booking->customer_id;
        $this->selectedEventTypeId = $booking->event_type_id;
        
        $this->selectedBranchId = (string) ($booking->branch_id ?: ($booking->hall?->branch_id ?: ''));
        
        $marqueeId = $booking->marquee_id ?: ($user ? $user->getActiveMarqueeId() : null);
        $this->marquee_id = $marqueeId;
        $accessibleBranches = $user ? $user->getAccessibleBranches($marqueeId) : collect();
        $this->branchesList = $accessibleBranches;

        $this->isMultiBranchUser = $accessibleBranches->count() > 1;
        $this->canChangeBranch = in_array($booking->booking_status, ['Draft', 'Reserved']) && ($isOwner || $this->isMultiBranchUser);

        $this->selectedHallIds = $booking->halls->pluck('id')->map(fn($id) => (string)$id)->toArray();
        if (empty($this->selectedHallIds) && $booking->hall_id) {
            $this->selectedHallIds = [(string)$booking->hall_id];
        }
        $this->selectedHallId = reset($this->selectedHallIds) ?: '';
        
        $this->selectedDate = $booking->booking_date->format('Y-m-d');
        
        $this->selectedSlotId = $booking->slot_id ?: '';
        $this->checkType = $booking->slot_id ? 'slot' : 'custom';
        
        $this->startTime = $booking->start_time->format('Y-m-d H:i:s');
        $this->endTime = $booking->end_time->format('Y-m-d H:i:s');

        $this->customStart = $booking->start_time->format('H:i');
        $this->customEnd = $booking->end_time->format('H:i');

        $this->selectedPackageId = $booking->package_id;
        $this->tentativeGuests = $booking->tentative_guests ?? $booking->guest_count;
        $this->confirmedGuests = $booking->confirmed_guests;
        $this->guestStatus = $booking->guest_status ?? ($booking->confirmed_guests ? 'Confirmed' : 'Tentative');
        $this->guestCount = $booking->confirmed_guests ?? $booking->tentative_guests ?? $booking->guest_count;
        $this->perPlatePrice = $booking->per_plate_price ?? 0.00;
        $this->hallCharges = $booking->hall_charges ?? 0.00;
        $this->extraCharges = $booking->extra_charges ?? 0.00;
        $this->discountAmount = $booking->discount_amount ?? 0.00;
        $this->securityDeposit = $booking->security_deposit ?? 0.00;

        $this->privacyRequired = (bool) $booking->privacy_required;
        $this->privacyLadiesPercentage = $booking->privacy_required ? $booking->privacy_ladies_percentage : '';
        $this->privacyGentsPercentage = $booking->privacy_required ? $booking->privacy_gents_percentage : '';
        $this->noFood = (bool)$booking->no_food;
        
        // Calculate original tax rate
        $this->packageAmount = $booking->package_amount ?? 0.00;
        $this->subtotal = $booking->subtotal ?? 0.00;
        $this->taxAmount = $booking->tax_amount ?? 0.00;
        $this->grandTotal = $booking->grand_total ?? 0.00;
        
        if ($this->subtotal > 0) {
            $this->taxRate = round(($this->taxAmount * 100) / $this->subtotal, 2);
        } else {
            $this->taxRate = 13.00;
            if ($this->selectedBranchId) {
                $branch = \App\Models\Branch::find($this->selectedBranchId);
                if ($branch && $branch->tax_rate !== null) {
                    $this->taxRate = (float) $branch->tax_rate;
                }
            }
        }

        $this->specialInstructions = $booking->special_instructions ?? '';
        $this->bookingStatus = $booking->booking_status ?? 'Draft';
        $this->paymentStatus = $booking->payment_status ?? 'Unpaid';

        $this->loadDropdowns();
        $this->loadExistingMenuItems();
        $this->loadExistingAddons();
        $this->loadSlotsAndCheck();
    }

    public function loadExistingAddons()
    {
        $marqueeId = $this->booking->marquee_id ?? auth()->user()->getActiveMarqueeId();
        $this->addonsList = ExtraService::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('service_name')
            ->get();

        // Initialize selectedAddons map
        $this->selectedAddons = [];
        foreach ($this->addonsList as $addon) {
            $this->selectedAddons[$addon->id] = [
                'selected' => false,
                'price' => $addon->default_price,
                'quantity' => 1,
                'name' => $addon->service_name,
            ];
        }

        // Map already saved addons
        foreach ($this->booking->extraServices as $saved) {
            $addonId = $saved->extra_service_id;
            if ($addonId && isset($this->selectedAddons[$addonId])) {
                $this->selectedAddons[$addonId]['selected'] = true;
                $this->selectedAddons[$addonId]['price'] = $saved->unit_price;
                $this->selectedAddons[$addonId]['quantity'] = $saved->quantity;
            } else {
                $this->selectedAddons['custom_' . $saved->id] = [
                    'selected' => true,
                    'price' => $saved->unit_price,
                    'quantity' => $saved->quantity,
                    'name' => $saved->service_name,
                ];
            }
        }
    }

    public function loadExistingMenuItems()
    {
        $this->bookingMenuItems = [];
        foreach ($this->booking->menuItems as $item) {
            $this->bookingMenuItems[] = [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'urdu_name' => $item->urdu_name,
                'custom_note' => $item->pivot->custom_note ?? '',
                'managed_by_host' => (bool)($item->pivot->managed_by_host ?? false),
            ];
        }
    }

    public function updatedSelectedBranchId($value)
    {
        if (!$this->canChangeBranch && (int)$value !== (int)$this->booking->branch_id) {
            session()->flash('error', 'Branch cannot be modified for confirmed or completed bookings.');
            $this->selectedBranchId = (string) $this->booking->branch_id;
            return;
        }

        $marqueeId = $this->booking->marquee_id ?: auth()->user()->getActiveMarqueeId();
        $this->selectedHallIds = [];
        $this->selectedHallId = '';

        if (!empty($value)) {
            $branch = \App\Models\Branch::find($value);
            if ($branch && $branch->tax_rate !== null) {
                $this->taxRate = (float) $branch->tax_rate;
            }

            $this->hallsList = Hall::where('marquee_id', $marqueeId)
                ->where('branch_id', $value)
                ->whereIn('status', ['active', 'Active'])
                ->orderBy('hall_name')
                ->get();

            $this->filteredHalls = $this->hallsList->toArray();

            if ($this->hallsList->isNotEmpty()) {
                $this->selectedHallIds = [(string) $this->hallsList->first()->id];
                $this->selectedHallId = $this->selectedHallIds[0];
                $this->hallCharges = (float) $this->hallsList->first()->default_booking_price;
            } else {
                $this->hallCharges = 0.00;
            }
        } else {
            $this->hallsList = collect();
            $this->filteredHalls = [];
            $this->hallCharges = 0.00;
        }

        $this->loadSlotsAndCheck();
        $this->recalculatePrices();
    }

    public function loadDropdowns()
    {
        $marqueeId = $this->booking->marquee_id ?? auth()->user()->getActiveMarqueeId();

        $this->customersList = Customer::where('marquee_id', $marqueeId)
            ->whereIn('status', ['active', 'Active'])
            ->orderBy('first_name')
            ->get();

        $this->eventTypesList = EventType::where('marquee_id', $marqueeId)
            ->whereIn('status', ['active', 'Active'])
            ->orderBy('sort_order')
            ->get();

        $this->hallsList = Hall::where('marquee_id', $marqueeId)
            ->whereIn('status', ['active', 'Active'])
            ->orderBy('hall_name')
            ->get();

        $this->packagesList = Package::where('marquee_id', $marqueeId)
            ->whereIn('status', ['active', 'Active'])
            ->orderBy('package_name')
            ->get();

        $this->menuItemsAutocomplete = MenuItem::where('marquee_id', $marqueeId)
            ->orderBy('item_name')
            ->get();

        $this->filteredEventTypes = $this->eventTypesList->toArray();
        $this->filteredHalls = $this->hallsList->toArray();

        // Initialize search values
        $selectedEvent = EventType::find($this->selectedEventTypeId);
        if ($selectedEvent) {
            $this->eventTypeSearch = $selectedEvent->event_type_name;
        }
    }

    public function updatedSelectedDate()
    {
        $this->resetSlotState();
        $this->loadSlotsAndCheck();
    }

    public function updatedCheckType()
    {
        $this->resetSlotState();
        $this->loadSlotsAndCheck();
    }

    public function updatedEventTypeSearch()
    {
        $user = auth()->user();
        $marqueeId = $this->marquee_id ?: ($user ? $user->getActiveMarqueeId() : null);

        if (empty($this->eventTypeSearch)) {
            $this->filteredEventTypes = $this->eventTypesList->toArray();
        } else {
            $term = '%' . $this->eventTypeSearch . '%';
            $this->filteredEventTypes = EventType::where('marquee_id', $marqueeId)
                ->whereIn('status', ['active', 'Active'])
                ->where('event_type_name', 'like', $term)
                ->orderBy('sort_order')
                ->get()
                ->toArray();
        }
    }

    public function selectEventType($id, $name)
    {
        $this->selectedEventTypeId = $id;
        $this->eventTypeSearch = $name;
    }

    public function updatedHallSearch()
    {
        $user = auth()->user();
        $marqueeId = $this->marquee_id ?: ($user ? $user->getActiveMarqueeId() : null);

        if (empty($this->hallSearch)) {
            $this->filteredHalls = $this->hallsList->toArray();
        } else {
            $term = '%' . $this->hallSearch . '%';
            $this->filteredHalls = Hall::where('marquee_id', $marqueeId)
                ->whereIn('status', ['active', 'Active'])
                ->where('hall_name', 'like', $term)
                ->orderBy('hall_name')
                ->get()
                ->toArray();
        }
    }

    public function toggleHall($id)
    {
        $id = (string)$id;
        if (in_array($id, $this->selectedHallIds)) {
            $this->selectedHallIds = array_diff($this->selectedHallIds, [$id]);
        } else {
            $this->selectedHallIds[] = $id;
        }
        $this->selectedHallIds = array_values($this->selectedHallIds);
        $this->selectedHallId = reset($this->selectedHallIds) ?: '';
        
        $this->resetSlotState();
        $this->loadSlotsAndCheck();
    }

    private function resetSlotState()
    {
        $this->selectedSlotId = '';
        $this->customStart = '';
        $this->customEnd = '';
        $this->startTime = '';
        $this->endTime = '';
        $this->isAvailable = true;
        $this->availabilityChecked = false;
        $this->conflictDetails = null;
    }

    public function loadSlotsAndCheck()
    {
        if (empty($this->selectedHallIds) || empty($this->selectedDate)) {
            $this->availableSlotsList = [];
            return;
        }

        $service = new AvailabilityService();
        $user = auth()->user();

        $selectedHalls = Hall::with(['slots' => function ($q) {
            $q->whereIn('slots.status', ['active', 'Active']);
        }])->whereIn('id', $this->selectedHallIds)->get();

        $marqueeId = $selectedHalls->first()?->marquee_id ?: ($this->marquee_id ?: ($user ? $user->getActiveMarqueeId() : null));

        // Check if selected halls have specific assigned slots configured
        $assignedSlotIds = collect();
        $hasSpecificAssignments = false;

        foreach ($selectedHalls as $hall) {
            $hallSlotIds = $hall->slots->pluck('id');
            if ($hallSlotIds->isNotEmpty()) {
                $hasSpecificAssignments = true;
                if ($assignedSlotIds->isEmpty()) {
                    $assignedSlotIds = $hallSlotIds;
                } else {
                    $assignedSlotIds = $assignedSlotIds->intersect($hallSlotIds);
                }
            }
        }

        if ($hasSpecificAssignments && $assignedSlotIds->isNotEmpty()) {
            $slots = Slot::whereIn('id', $assignedSlotIds)
                ->whereIn('status', ['active', 'Active'])
                ->orderBy('start_time')
                ->get();
        } else {
            $slots = Slot::where('marquee_id', $marqueeId)
                ->whereIn('status', ['active', 'Active'])
                ->orderBy('start_time')
                ->get();
        }

        $this->availableSlotsList = [];

        foreach ($slots as $slot) {
            $isSlotAvailable = true;
            foreach ($this->selectedHallIds as $hallId) {
                if (!$service->checkAvailability(
                    $hallId,
                    $this->selectedDate,
                    $slot->start_time,
                    $slot->end_time,
                    $this->booking->id
                )) {
                    $isSlotAvailable = false;
                    break;
                }
            }

            $this->availableSlotsList[] = [
                'id' => $slot->id,
                'name' => $slot->slot_name,
                'start' => Carbon::parse($slot->start_time)->format('h:i A'),
                'end' => Carbon::parse($slot->end_time)->format('h:i A'),
                'is_available' => $isSlotAvailable,
            ];
        }
    }

    public function updatedSelectedSlotId()
    {
        if (empty($this->selectedSlotId)) {
            $this->resetSlotState();
            return;
        }

        $slot = Slot::findOrFail($this->selectedSlotId);
        $this->customStart = Carbon::parse($slot->start_time)->format('H:i');
        $this->customEnd = Carbon::parse($slot->end_time)->format('H:i');
        
        $this->calculateTimes();
        $this->runAvailabilityCheck();
    }

    public function updatedCustomStart()
    {
        $this->calculateTimes();
        $this->runAvailabilityCheck();
    }

    public function updatedCustomEnd()
    {
        $this->calculateTimes();
        $this->runAvailabilityCheck();
    }

    private function calculateTimes()
    {
        if (empty($this->selectedDate) || empty($this->customStart) || empty($this->customEnd)) {
            return;
        }

        $startStr = $this->selectedDate . ' ' . $this->customStart . ':00';
        $endStr = $this->selectedDate . ' ' . $this->customEnd . ':00';

        $start = Carbon::parse($startStr);
        $end = Carbon::parse($endStr);

        if ($end->lt($start)) {
            $end = $end->addDay();
        }

        $this->startTime = $start->format('Y-m-d H:i:s');
        $this->endTime = $end->format('Y-m-d H:i:s');
    }

    private function runAvailabilityCheck()
    {
        if (empty($this->selectedHallIds) || empty($this->selectedDate) || empty($this->startTime) || empty($this->endTime)) {
            $this->availabilityChecked = false;
            return;
        }

        $service = new AvailabilityService();
        $this->isAvailable = true;
        $this->conflictDetails = null;

        foreach ($this->selectedHallIds as $hallId) {
            $conflicting = $service->getConflictingBooking(
                $hallId,
                $this->selectedDate,
                Carbon::parse($this->startTime)->format('H:i:s'),
                Carbon::parse($this->endTime)->format('H:i:s'),
                $this->booking->id
            );

            if ($conflicting) {
                $this->isAvailable = false;
                $this->conflictDetails = $conflicting;
                break;
            }
        }

        $this->availabilityChecked = true;
    }

    public function updatedSelectedPackageId()
    {
        if (empty($this->selectedPackageId)) {
            $this->perPlatePrice = 0.00;
            $this->bookingMenuItems = [];
            $this->recalculatePrices();
            return;
        }

        $package = Package::with('menuItems')->findOrFail($this->selectedPackageId);
        $this->perPlatePrice = $package->per_plate_price;
        $this->guestCount = $package->minimum_guests ?: 100;

        // Copy package menu items to booking level
        $this->bookingMenuItems = [];
        foreach ($package->menuItems as $item) {
            $this->bookingMenuItems[] = [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'urdu_name' => $item->urdu_name,
                'custom_note' => '',
            ];
        }

        $this->recalculatePrices();
    }

    public function updatedPerPlatePrice() { $this->recalculatePrices(); }
    public function updatedHallCharges() { $this->recalculatePrices(); }
    public function updatedExtraCharges() { $this->recalculatePrices(); }
    public function updatedDiscountAmount() { $this->recalculatePrices(); }
    public function updatedSecurityDeposit() { $this->recalculatePrices(); }
    public function updatedTaxRate() { $this->recalculatePrices(); }

    public function updatedTentativeGuests() { $this->syncGuestCounts(); $this->recalculatePrices(); }
    public function updatedConfirmedGuests() { $this->syncGuestCounts(); $this->recalculatePrices(); }

    public function syncGuestCounts()
    {
        if ($this->guestCount === '' || $this->guestCount === 0 || $this->guestCount === '0') {
            $this->tentativeGuests = 0;
        }

        $tentative = is_numeric($this->tentativeGuests)
            ? intval($this->tentativeGuests)
            : (is_numeric($this->guestCount) ? intval($this->guestCount) : 0);

        $confirmed = (is_numeric($this->confirmedGuests) && intval($this->confirmedGuests) > 0) ? intval($this->confirmedGuests) : null;

        $this->tentativeGuests = $tentative;
        $this->confirmedGuests = $confirmed;

        if (!is_null($confirmed)) {
            $this->guestCount = $confirmed;
            $this->guestStatus = 'Confirmed';

            if ($confirmed > $tentative) {
                session()->flash('warning', "Notice: Confirmed guests ({$confirmed}) exceed tentative guest estimate ({$tentative}).");
            }
        } else {
            $this->guestCount = $tentative;
            $this->guestStatus = 'Tentative';
        }
    }

    public function updatedGuestCount()
    {
        $this->tentativeGuests = is_numeric($this->guestCount) ? intval($this->guestCount) : 0;
        $this->syncGuestCounts();
        $this->recalculatePrices();
    }

    public function updatedSelectedAddons()
    {
        $this->recalculatePrices();
    }

    public function updatedMenuItemSearch()
    {
        $user = auth()->user();
        $marqueeId = $this->marquee_id ?: ($user ? $user->getActiveMarqueeId() : null);
        if (empty($this->menuItemSearch)) {
            $this->menuItemsAutocomplete = [];
            return;
        }

        $term = '%' . $this->menuItemSearch . '%';
        $this->menuItemsAutocomplete = \App\Models\MenuItem::with('category')->where('marquee_id', $marqueeId)
            ->where('item_name', 'like', $term)
            ->orderBy('item_name')
            ->get();
    }

    public function selectMenuItem($id)
    {
        $item = MenuItem::find($id);
        if ($item) {
            // Check for duplicates
            $exists = false;
            foreach ($this->bookingMenuItems as $existing) {
                if ($existing['id'] == $item->id) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $this->bookingMenuItems[] = [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'urdu_name' => $item->urdu_name,
                    'custom_note' => '',
                    'managed_by_host' => $this->noFood ? true : false,
                ];
            }
        }
        $this->menuItemSearch = '';
        $this->updatedMenuItemSearch();
    }

    public function addMenuItem()
    {
        if (empty($this->selectedMenuItemToAdd)) {
            return;
        }

        $this->selectMenuItem($this->selectedMenuItemToAdd);
        $this->selectedMenuItemToAdd = '';
    }

    public function removeMenuItem($index)
    {
        if (isset($this->bookingMenuItems[$index])) {
            unset($this->bookingMenuItems[$index]);
            $this->bookingMenuItems = array_values($this->bookingMenuItems); // Reset keys
        }
    }

    public function moveMenuItemUp($index)
    {
        if ($index > 0 && isset($this->bookingMenuItems[$index])) {
            $temp = $this->bookingMenuItems[$index - 1];
            $this->bookingMenuItems[$index - 1] = $this->bookingMenuItems[$index];
            $this->bookingMenuItems[$index] = $temp;
        }
    }

    public function moveMenuItemDown($index)
    {
        if ($index < count($this->bookingMenuItems) - 1 && isset($this->bookingMenuItems[$index])) {
            $temp = $this->bookingMenuItems[$index + 1];
            $this->bookingMenuItems[$index + 1] = $this->bookingMenuItems[$index];
            $this->bookingMenuItems[$index] = $temp;
        }
    }

    public function recalculatePrices()
    {
        if ($this->noFood) {
            $this->perPlatePrice = 0.00;
            $this->selectedPackageId = '';
            $this->bookingMenuItems = array_map(function($item) {
                $item['managed_by_host'] = true;
                return $item;
            }, $this->bookingMenuItems);
        }

        // Calculate the sum of selected extra services (add-ons)
        $addonsSum = 0.00;
        foreach ($this->selectedAddons as $addonId => $addon) {
            if (!empty($addon['selected'])) {
                $price = is_numeric($addon['price']) ? floatval($addon['price']) : 0.00;
                $quantity = is_numeric($addon['quantity']) ? intval($addon['quantity']) : 1;
                $addonsSum += $price * $quantity;
            }
        }
        if ($this->guestCount === '' || $this->guestCount === 0 || $this->guestCount === '0') {
            $this->tentativeGuests = 0;
        } elseif (is_numeric($this->guestCount) && is_null($this->confirmedGuests)) {
            $this->tentativeGuests = intval($this->guestCount);
        }

        $this->syncGuestCounts();

        // Typecast/sanitize inputs to numeric types to prevent TypeError in number_format()
        $this->guestCount = is_numeric($this->guestCount) ? intval($this->guestCount) : 0;
        $this->perPlatePrice = is_numeric($this->perPlatePrice) ? floatval($this->perPlatePrice) : 0.00;
        $this->hallCharges = is_numeric($this->hallCharges) ? floatval($this->hallCharges) : 0.00;
        $this->extraCharges = is_numeric($this->extraCharges) ? floatval($this->extraCharges) : 0.00;
        $this->discountAmount = is_numeric($this->discountAmount) ? floatval($this->discountAmount) : 0.00;
        $this->securityDeposit = is_numeric($this->securityDeposit) ? floatval($this->securityDeposit) : 0.00;
        $this->taxRate = is_numeric($this->taxRate) ? floatval($this->taxRate) : 0.00;

        $pricing = BookingPricingService::calculate([
            'guest_count' => $this->guestCount,
            'per_plate_price' => $this->perPlatePrice,
            'hall_charges' => $this->hallCharges,
            'extra_charges' => $this->extraCharges,
            'discount_amount' => $this->discountAmount,
            'security_deposit' => $this->securityDeposit,
            'tax_rate' => $this->taxRate,
        ]);

        $this->packageAmount = $pricing['package_amount'];
        $this->subtotal = $pricing['subtotal'];
        $this->taxAmount = $pricing['tax_amount'];
        $this->grandTotal = $pricing['grand_total'];
    }

    /**
     * Save updates to the Booking model.
     */
    public function save()
    {
        // Owner/Superadmin lock validation on save
        $user = auth()->user();
        $isOwner = $user->role && in_array($user->role->name, ['owner', 'super_admin']);
        $isLocked = in_array($this->booking->booking_status, ['Completed', 'Cancelled']);

        if ($isLocked && !$isOwner) {
            $this->addError('submission', 'Only owners or super admins can edit Completed or Cancelled bookings.');
            return;
        }

        $rules = [
            'selectedBranchId' => 'required|exists:branches,id',
            'selectedCustomerId' => 'required|exists:customers,id',
            'selectedEventTypeId' => 'required|exists:event_types,id',
            'selectedHallIds' => 'required|array|min:1',
            'selectedDate' => 'required|date',
            'startTime' => 'required',
            'endTime' => 'required',
            'guestCount' => 'required|integer|min:1',
            'perPlatePrice' => 'required|numeric|min:0',
            'hallCharges' => 'required|numeric|min:0',
            'extraCharges' => 'required|numeric|min:0',
            'discountAmount' => 'required|numeric|min:0',
            'securityDeposit' => 'required|numeric|min:0',
            'taxRate' => 'required|numeric|min:0',
            'bookingStatus' => 'required|in:Draft,Reserved,Confirmed,Completed,Cancelled,Rejected',
            'paymentStatus' => 'required|in:Unpaid,Partially Paid,Paid,Refunded',
        ];

        if (!$this->noFood) {
            $rules['selectedPackageId'] = 'required|exists:packages,id';
        }

        if ($this->privacyRequired) {
            $rules['privacyLadiesPercentage'] = 'required|integer|min:0|max:100';
            $rules['privacyGentsPercentage'] = 'required|integer|min:0|max:100';
        }

        $this->validate($rules, [
            'selectedBranchId.required' => 'Please select a branch for this booking.',
            'selectedBranchId.exists' => 'The selected branch is invalid.',
        ]);

        $user = auth()->user();
        $marqueeId = $this->booking->marquee_id ?? $user->getActiveMarqueeId();

        // If branch changed, verify authorization and lock status
        if ((int)$this->selectedBranchId !== (int)$this->booking->branch_id) {
            if (!$this->canChangeBranch) {
                $this->addError('selectedBranchId', 'Branch cannot be modified for confirmed or completed bookings.');
                return;
            }
            if (!$user->hasAccessToBranch($this->selectedBranchId, $marqueeId)) {
                $this->addError('selectedBranchId', 'You are not authorized to assign this branch.');
                return;
            }
        }

        $validHallCount = Hall::where('marquee_id', $marqueeId)
            ->where('branch_id', $this->selectedBranchId)
            ->whereIn('id', $this->selectedHallIds)
            ->count();

        if ($validHallCount !== count($this->selectedHallIds)) {
            $this->addError('selectedHallIds', 'One or more selected halls do not belong to the chosen branch.');
            return;
        }

        if ($this->bookingStatus === 'Completed' && Carbon::parse($this->selectedDate)->startOfDay()->gt(Carbon::today())) {
            $this->addError('bookingStatus', 'Future bookings cannot be marked as Completed.');
            return;
        }

        if ($this->privacyRequired) {
            $ladies = intval($this->privacyLadiesPercentage);
            $gents = intval($this->privacyGentsPercentage);
            if (($ladies + $gents) !== 100) {
                $this->addError('privacyLadiesPercentage', 'Ladies and Gents percentages must total exactly 100%.');
                $this->addError('privacyGentsPercentage', 'Ladies and Gents percentages must total exactly 100%.');
                return;
            }
        }

        $userId = $user->id;
        $primaryHallId = reset($this->selectedHallIds);
        $branchId = (int) $this->selectedBranchId;

        try {
            DB::transaction(function () use ($marqueeId, $userId, $branchId, $primaryHallId) {
                $service = new AvailabilityService();

                // Shared lock checking & availability verification
                foreach ($this->selectedHallIds as $hId) {
                    DB::table('bookings')
                        ->where('marquee_id', $marqueeId)
                        ->where('hall_id', $hId)
                        ->where('booking_date', $this->selectedDate)
                        ->where('id', '!=', $this->booking->id)
                        ->lockForUpdate()
                        ->get();

                    $isStillAvailable = $service->checkAvailability(
                        $hId,
                        $this->selectedDate,
                        Carbon::parse($this->startTime)->format('H:i:s'),
                        Carbon::parse($this->endTime)->format('H:i:s'),
                        $this->booking->id
                    );

                    // Check for double-booking unless we are saving as Draft or Cancelled/Rejected
                    if (in_array($this->bookingStatus, ['Reserved', 'Confirmed']) && !$isStillAvailable) {
                        $hallModel = Hall::find($hId);
                        $hallName = $hallModel ? $hallModel->hall_name : 'Hall';
                        throw new \Exception("Double-booking clash: The selected slot overlaps with another reservation in {$hallName}.");
                    }
                }

                $oldStatus = $this->booking->booking_status;
                $oldPaymentStatus = $this->booking->payment_status;

                // Update
                $this->booking->update([
                    'branch_id' => $branchId,
                    'customer_id' => $this->selectedCustomerId,
                    'event_type_id' => $this->selectedEventTypeId,
                    'hall_id' => $primaryHallId, // primary hall fallback
                    'slot_id' => $this->selectedSlotId ?: null,
                    'package_id' => $this->noFood ? null : $this->selectedPackageId,
                    'booking_date' => $this->selectedDate,
                    'start_time' => $this->startTime,
                    'end_time' => $this->endTime,
                    'guest_count' => $this->guestCount,
                    'tentative_guests' => is_numeric($this->tentativeGuests) ? intval($this->tentativeGuests) : $this->guestCount,
                    'confirmed_guests' => (is_numeric($this->confirmedGuests) && intval($this->confirmedGuests) > 0) ? intval($this->confirmedGuests) : null,
                    'guest_status' => $this->guestStatus ?: 'Tentative',
                    'per_plate_price' => $this->perPlatePrice,
                    'package_amount' => $this->packageAmount,
                    'hall_charges' => $this->hallCharges,
                    'extra_charges' => $this->extraCharges,
                    'discount_amount' => $this->discountAmount,
                    'security_deposit' => $this->securityDeposit,
                    'tax_amount' => $this->taxAmount,
                    'subtotal' => $this->subtotal,
                    'grand_total' => $this->grandTotal,
                    'special_instructions' => $this->specialInstructions ?: null,
                    'booking_status' => $this->bookingStatus,
                    'payment_status' => $this->paymentStatus,
                    'no_food' => $this->noFood,
                    'privacy_required' => $this->privacyRequired,
                    'privacy_ladies_percentage' => $this->privacyRequired ? intval($this->privacyLadiesPercentage) : null,
                    'privacy_gents_percentage' => $this->privacyRequired ? intval($this->privacyGentsPercentage) : null,
                ]);

                // Sync allocated halls pivot table
                $this->booking->halls()->sync($this->selectedHallIds);

                // Sync extra services (add-ons)
                $this->booking->extraServices()->delete();
                foreach ($this->selectedAddons as $addonId => $addon) {
                    if (!empty($addon['selected'])) {
                        $price = floatval($addon['price']);
                        $qty = intval($addon['quantity']);
                        \App\Models\BookingExtraService::create([
                            'booking_id' => $this->booking->id,
                            'extra_service_id' => is_numeric($addonId) ? $addonId : null,
                            'service_name' => $addon['name'],
                            'unit_price' => $price,
                            'quantity' => $qty,
                            'total_price' => $price * $qty,
                        ]);
                    }
                }

                // Sync customized menu items with managed_by_host pivot values
                $this->booking->menuItems()->detach();
                foreach ($this->bookingMenuItems as $index => $menuItem) {
                    \App\Models\BookingMenuItem::create([
                        'booking_id' => $this->booking->id,
                        'menu_item_id' => $menuItem['id'],
                        'custom_note' => $menuItem['custom_note'] ?: null,
                        'managed_by_host' => !empty($menuItem['managed_by_host']),
                        'sort_order' => $index,
                    ]);
                }

                // Create history record if anything changed
                if ($oldStatus !== $this->bookingStatus || $oldPaymentStatus !== $this->paymentStatus) {
                    BookingHistory::create([
                        'booking_id' => $this->booking->id,
                        'user_id' => $userId,
                        'status_from' => $oldStatus,
                        'status_to' => $this->bookingStatus,
                        'payment_status_from' => $oldPaymentStatus,
                        'payment_status_to' => $this->paymentStatus,
                        'notes' => 'Booking details updated by staff.',
                    ]);
                }
            });

            session()->flash('success', 'Booking #' . $this->booking->booking_number . ' has been updated successfully.');
            return redirect()->route('bookings.show', $this->booking->id);

        } catch (\Exception $e) {
            $this->addError('submission', $e->getMessage());
        }
    }

    public function render()
    {
        $this->recalculatePrices();
        return view('livewire.booking-edit');
    }
}
