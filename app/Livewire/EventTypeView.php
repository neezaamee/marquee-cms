<?php

namespace App\Livewire;

use App\Models\EventType;
use Livewire\Component;

class EventTypeView extends Component
{
    public $eventType;

    public function mount(EventType $eventType)
    {
        $this->eventType = $eventType;
    }

    public function render()
    {
        return view('livewire.event-type-view');
    }
}
