@extends('layouts.admin')

@section('title', 'Subscription Plan Details')

@section('content')
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Plan Details: {{ $plan->name }}</h5>
            <a href="{{ route('subscription-plans.index') }}" class="btn btn-falcon-default btn-sm">
                <span class="fas fa-arrow-left me-1"></span> Back to Plans
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6>Plan Info</h6>
                    <table class="table table-bordered fs-10">
                        <tr>
                            <th>Slug</th>
                            <td>{{ $plan->slug }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge badge-subtle-{{ $plan->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                                    {{ ucfirst($plan->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Popular</th>
                            <td>{{ $plan->is_popular ? 'Yes' : 'No' }}</td>
                        </tr>
                        <tr>
                            <th>Sort Order</th>
                            <td>{{ $plan->sort_order }}</td>
                        </tr>
                        <tr>
                            <th>Currency</th>
                            <td>{{ $plan->currency }}</td>
                        </tr>
                        <tr>
                            <th>Trial Days</th>
                            <td>{{ $plan->trial_days }} days</td>
                        </tr>
                        <tr>
                            <th>Max Storage</th>
                            <td>{{ $plan->max_storage }} MB</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6 mb-3">
                    <h6>Pricing & Billing</h6>
                    <table class="table table-bordered fs-10">
                        <tr>
                            <th>Monthly Price</th>
                            <td>{{ number_format($plan->monthly_price, 2) }} {{ $plan->currency }}</td>
                        </tr>
                        <tr>
                            <th>Quarterly Price</th>
                            <td>{{ number_format($plan->quarterly_price, 2) }} {{ $plan->currency }}</td>
                        </tr>
                        <tr>
                            <th>Semi-Annual Price</th>
                            <td>{{ number_format($plan->semi_annual_price, 2) }} {{ $plan->currency }}</td>
                        </tr>
                        <tr>
                            <th>Annual Price</th>
                            <td>{{ number_format($plan->annual_price, 2) }} {{ $plan->currency }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6>Mapped Features</h6>
                    @if($plan->planFeatures->count() > 0)
                        <ul class="list-group list-group-flush fs-10">
                            @foreach($plan->planFeatures as $feature)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $feature->feature_name }}</strong> 
                                        <small class="text-muted d-block">{{ $feature->description }}</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">{{ $feature->pivot->limit_value }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted fs-10">No features mapped yet.</p>
                    @endif
                </div>

                <div class="col-md-6 mb-3">
                    <h6>Mapped Billing Cycles</h6>
                    @if($plan->billingCycles->count() > 0)
                        <ul class="list-group list-group-flush fs-10">
                            @foreach($plan->billingCycles as $cycle)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $cycle->cycle_name }}</strong>
                                        <small class="text-muted d-block">{{ $cycle->duration_in_months }} months</small>
                                    </div>
                                    <span class="badge bg-success rounded-pill">-{{ $cycle->discount_percentage }}%</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted fs-10">No billing cycles mapped yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
