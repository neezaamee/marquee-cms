@extends('layouts.admin')

@section('title', 'Booking Calendar')

@section('content')
<div class="card mb-3">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-falcon-default btn-sm" id="btn-prev"><span class="fas fa-chevron-left"></span></button>
            <button class="btn btn-falcon-default btn-sm" id="btn-today">Today</button>
            <button class="btn btn-falcon-default btn-sm" id="btn-next"><span class="fas fa-chevron-right"></span></button>
            <h5 class="mb-0 fs-9 calendar-title ms-2 text-primary fw-bold" id="calendar-title-display"></h5>
        </div>
        <div class="d-flex align-items-center gap-1">
            <button class="btn btn-falcon-default btn-sm active" data-view="dayGridMonth">Month</button>
            <button class="btn btn-falcon-default btn-sm" data-view="timeGridWeek">Week</button>
            <button class="btn btn-falcon-default btn-sm" data-view="timeGridDay">Day</button>
            <button class="btn btn-falcon-default btn-sm" data-view="listMonth">List</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="bookingCalendar" style="min-height: 700px; padding: 15px;"></div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="calendarEventModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-primary"><span class="fas fa-calendar-check me-2"></span>Event Details</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <h5 class="fw-bold mb-1 text-dark" id="modal-event-title"></h5>
                    <p class="text-600 fs-11" id="modal-event-time"></p>
                </div>
                <hr />
                <div class="mb-3">
                    <p class="mb-1 text-800 fs-12" id="modal-event-desc" style="white-space: pre-line;"></p>
                </div>
                <div class="text-end">
                    <a href="" class="btn btn-primary btn-sm" id="modal-event-link"><span class="fas fa-eye me-1"></span>View Full Details</a>
                    <button class="btn btn-secondary btn-sm" type="button" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendors/fullcalendar/index.global.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('bookingCalendar');
        var eventsData = @json($events);

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: false, // hidden since we use custom controls
            height: 'auto',
            stickyHeaderDates: false,
            events: eventsData,
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                omitZeroMinute: true,
                meridiem: true
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault(); // prevent immediate browser redirect

                // Populate modal data
                document.getElementById('modal-event-title').textContent = info.event.title;
                
                // Formatting event start & end time
                var startFormatted = info.event.start ? info.event.start.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : '';
                var startHours = info.event.start ? info.event.start.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' }) : '';
                var endHours = info.event.end ? info.event.end.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' }) : '';
                document.getElementById('modal-event-time').textContent = startFormatted + ' | ' + startHours + ' - ' + endHours;
                
                // Description and link
                document.getElementById('modal-event-desc').textContent = info.event.extendedProps.description || '';
                document.getElementById('modal-event-link').setAttribute('href', info.event.url);

                // Show modal
                var myModal = new bootstrap.Modal(document.getElementById('calendarEventModal'));
                myModal.show();
            }
        });

        calendar.render();

        // Update Title Display
        function updateTitle() {
            document.getElementById('calendar-title-display').textContent = calendar.view.title;
        }
        updateTitle();

        // Custom Navigation Buttons
        document.getElementById('btn-prev').addEventListener('click', function () {
            calendar.prev();
            updateTitle();
        });

        document.getElementById('btn-next').addEventListener('click', function () {
            calendar.next();
            updateTitle();
        });

        document.getElementById('btn-today').addEventListener('click', function () {
            calendar.today();
            updateTitle();
        });

        // Custom View Change Buttons
        var viewButtons = document.querySelectorAll('[data-view]');
        viewButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                viewButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                var viewName = button.getAttribute('data-view');
                calendar.changeView(viewName);
                updateTitle();
            });
        });
    });
</script>
@endsection
