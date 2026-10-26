<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-book me-2 text-primary"></span>General Ledger Inquiry</h5>

            @if($ledgerData)
                <div class="d-flex align-items-center flex-wrap gap-2 d-print-none">
                    <!-- Share / Deep Link Button -->
                    <div x-data="{ copied: false }" class="d-inline-block">
                        <button type="button" 
                                @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2500)" 
                                class="btn btn-outline-primary btn-sm"
                                title="Copy shareable link with active filters">
                            <span class="fas fa-share-alt me-1"></span>
                            <span x-show="!copied">Share</span>
                            <span x-show="copied" style="display: none;" class="text-success fw-bold"><i class="fas fa-check me-1"></i>Copied!</span>
                        </button>
                    </div>

                    <!-- Export Excel / CSV -->
                    <button wire:click="exportExcel" 
                            wire:loading.attr="disabled"
                            type="button" 
                            class="btn btn-outline-success btn-sm" 
                            title="Export statement as CSV / Excel">
                        <span wire:loading.remove wire:target="exportExcel"><span class="fas fa-file-excel me-1"></span>Excel</span>
                        <span wire:loading wire:target="exportExcel"><span class="spinner-border spinner-border-sm me-1"></span>Exporting...</span>
                    </button>

                    <!-- Export PDF -->
                    <a href="{{ route('finance.general-ledger.pdf', [
                            'account_id' => $account_id,
                            'financial_year_id' => $financial_year_id,
                            'branch_id' => $branch_id,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'is_saas' => $isSaas ? 1 : 0
                        ]) }}" 
                       target="_blank" 
                       class="btn btn-outline-danger btn-sm" 
                       title="Export statement as PDF">
                        <span class="fas fa-file-pdf me-1"></span>PDF
                    </a>

                    <!-- Print View -->
                    <button type="button" 
                            onclick="window.print()" 
                            class="btn btn-outline-secondary btn-sm" 
                            title="Print this ledger statement">
                        <span class="fas fa-print me-1"></span>Print
                    </button>
                </div>
            @endif
        </div>

        <div class="card-body bg-light border-top border-bottom py-3 d-print-none">
            <form wire:submit.prevent="generateReport">
                <div class="row g-3">
                    <!-- Searchable Account Selector -->
                    <div class="col-md-3 position-relative" x-data="{
                        open: false,
                        search: '',
                        activeIndex: -1,
                        selectedId: @entangle('account_id').live,
                        accounts: {{ json_encode($accountsFormatted) }},
                        init() {
                            this.updateSearchLabel();
                            this.$watch('selectedId', () => this.updateSearchLabel());
                        },
                        updateSearchLabel() {
                            const found = this.accounts.find(a => a.id == this.selectedId);
                            if (found) {
                                this.search = found.label || `[${found.code}] ${found.name}`;
                            } else {
                                this.search = '';
                            }
                        },
                        get filteredList() {
                            if (!this.search || this.isMatched()) {
                                return this.accounts;
                            }
                            const q = this.search.toLowerCase().trim();
                            return this.accounts.filter(a => 
                                (a.name && a.name.toLowerCase().includes(q)) || 
                                (a.code && a.code.toLowerCase().includes(q)) ||
                                (a.nature && a.nature.toLowerCase().includes(q))
                            );
                        },
                        isMatched() {
                            const found = this.accounts.find(a => a.id == this.selectedId);
                            return found && this.search === (found.label || `[${found.code}] ${found.name}`);
                        },
                        selectAccount(acc) {
                            this.selectedId = acc.id;
                            this.search = acc.label || `[${acc.code}] ${acc.name}`;
                            this.open = false;
                            this.activeIndex = -1;
                            $wire.set('account_id', acc.id);
                        },
                        clearSelection() {
                            this.selectedId = '';
                            this.search = '';
                            this.open = true;
                            this.activeIndex = -1;
                            $wire.set('account_id', '');
                            this.$nextTick(() => this.$refs.accSearchInput.focus());
                        },
                        handleKeydown(e) {
                            const list = this.filteredList;
                            if (!this.open) {
                                if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
                                    e.preventDefault();
                                    this.open = true;
                                    this.activeIndex = list.length > 0 ? 0 : -1;
                                }
                                return;
                            }
                            if (e.key === 'ArrowDown') {
                                e.preventDefault();
                                if (list.length > 0) {
                                    this.activeIndex = (this.activeIndex + 1) % list.length;
                                    this.scrollToActive();
                                }
                            } else if (e.key === 'ArrowUp') {
                                e.preventDefault();
                                if (list.length > 0) {
                                    this.activeIndex = (this.activeIndex - 1 + list.length) % list.length;
                                    this.scrollToActive();
                                }
                            } else if (e.key === 'Enter') {
                                e.preventDefault();
                                if (this.activeIndex >= 0 && this.activeIndex < list.length) {
                                    this.selectAccount(list[this.activeIndex]);
                                }
                            } else if (e.key === 'Escape') {
                                this.open = false;
                            }
                        },
                        scrollToActive() {
                            this.$nextTick(() => {
                                const listEl = this.$refs.accList;
                                if (listEl && listEl.children[this.activeIndex]) {
                                    listEl.children[this.activeIndex].scrollIntoView({ block: 'nearest' });
                                }
                            });
                        }
                    }" @click.outside="open = false">
                        <label class="form-label fs-11 fw-bold mb-1 d-flex justify-content-between align-items-center" for="gl_account_search">
                            <span>Account <span class="text-danger">*</span></span>
                            <span class="text-muted fw-normal fs-11"><i class="fas fa-search me-1"></i>Searchable</span>
                        </label>
                        
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted"><i class="fas fa-book"></i></span>
                            <input type="text"
                                   id="gl_account_search"
                                   x-ref="accSearchInput"
                                   x-model="search"
                                   @focus="open = true"
                                   @input="open = true; activeIndex = 0"
                                   @keydown="handleKeydown($event)"
                                   class="form-control form-control-sm @error('account_id') is-invalid @enderror"
                                   placeholder="Type code, name, or nature...">
                            <button x-show="selectedId"
                                    @click="clearSelection()"
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    title="Clear selection">
                                <i class="fas fa-times"></i>
                            </button>
                            <button @click="open = !open"
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-chevron-down fs-11" :class="{ 'fa-rotate-180': open }"></i>
                            </button>
                        </div>
                        <input type="hidden" name="account_id" wire:model="account_id" id="gl_account">
                        @error('account_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                        <!-- Dropdown List -->
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 transform scale-100"
                             x-transition:leave-end="opacity-0 transform scale-95"
                             class="position-absolute start-0 end-0 bg-white border border-200 rounded shadow-lg mt-1 py-1"
                             style="max-height: 240px; overflow-y: auto; z-index: 1060; display: none;">
                            <div x-ref="accList">
                                <template x-for="(acc, index) in filteredList" :key="acc.id">
                                    <div @click="selectAccount(acc)"
                                         @mouseenter="activeIndex = index"
                                         :class="{
                                             'bg-primary text-white': activeIndex === index,
                                             'bg-primary-subtle text-primary fw-bold': selectedId == acc.id && activeIndex !== index,
                                             'text-dark': selectedId != acc.id && activeIndex !== index
                                         }"
                                         class="px-3 py-2 d-flex justify-content-between align-items-center cursor-pointer border-bottom border-light">
                                        <div>
                                            <span class="font-monospace fw-bold me-1" x-text="'[' + acc.code + ']'"></span>
                                            <span x-text="acc.name"></span>
                                        </div>
                                        <span class="badge" 
                                              :class="activeIndex === index ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary'"
                                              x-text="acc.nature"></span>
                                    </div>
                                </template>
                                <div x-show="filteredList.length === 0" class="px-3 py-2 text-muted text-center fs-11">
                                    No accounts matching "<span x-text="search" class="fw-bold"></span>"
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Year -->
                    <div class="col-md-2">
                        <label class="form-label fs-11 fw-bold mb-1" for="gl_fy">Financial Year <span class="text-danger">*</span></label>
                        <select wire:model.live="financial_year_id" class="form-select form-select-sm @error('financial_year_id') is-invalid @enderror" id="gl_fy">
                            <option value="">Select FY</option>
                            @foreach($financialYears as $fy)
                                <option value="{{ $fy->id }}">{{ $fy->name }}</option>
                            @endforeach
                        </select>
                        @error('financial_year_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Branch (if not SaaS) -->
                    @if(!$isSaas)
                    <div class="col-md-2">
                        <label class="form-label fs-11 fw-bold mb-1" for="gl_branch">Branch</label>
                        <select wire:model="branch_id" class="form-select form-select-sm" id="gl_branch" {{ auth()->user()->branch_id && !auth()->user()->isSuperAdmin() ? 'disabled' : '' }}>
                            <option value="">Central / All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Start Date -->
                    <div class="col-md-2">
                        <label class="form-label fs-11 fw-bold mb-1" for="gl_start">Start Date</label>
                        <input wire:model="startDate" type="date" class="form-control form-control-sm @error('startDate') is-invalid @enderror" id="gl_start">
                        @error('startDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- End Date -->
                    <div class="col-md-2">
                        <label class="form-label fs-11 fw-bold mb-1" for="gl_end">End Date</label>
                        <input wire:model="endDate" type="date" class="form-control form-control-sm @error('endDate') is-invalid @enderror" id="gl_end">
                        @error('endDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Search Button -->
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="generateReport">Search</span>
                            <span wire:loading wire:target="generateReport"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-danger-800">{{ session('error') }}</p>
                </div>
            @endif

            @if($ledgerData)
                <!-- Ledger Header Info -->
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light flex-wrap gap-2">
                    <div>
                        <h6 class="mb-1 text-primary">[{{ $ledgerData['account']->account_code }}] {{ $ledgerData['account']->name }}</h6>
                        <span class="text-muted fs-11">
                            Nature: <strong>{{ strtoupper($ledgerData['account']->nature) }}</strong> 
                            | Period: <strong>{{ date('M d, Y', strtotime($startDate)) }}</strong> to <strong>{{ date('M d, Y', strtotime($endDate)) }}</strong>
                        </span>
                    </div>
                    <div>
                        @if($isSaas)
                            <span class="badge badge-subtle-primary rounded-pill">SaaS Platform</span>
                        @elseif($branch_id)
                            <span class="badge badge-subtle-primary rounded-pill">{{ \App\Models\Branch::find($branch_id)?->name ?? 'Branch' }}</span>
                        @else
                            <span class="badge badge-subtle-secondary rounded-pill">Central (All Branches)</span>
                        @endif
                    </div>
                </div>

                <!-- Ledger Quick Stats Cards -->
                <div class="row g-2 p-3 bg-light border-bottom">
                    <div class="col-md-3">
                        <div class="card shadow-none border border-200">
                            <div class="card-body p-3">
                                <p class="text-muted fs-11 mb-1 fw-bold">Opening Balance</p>
                                <h5 class="mb-0 font-monospace">{{ number_format($ledgerData['opening_balance'], 2) }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-none border border-200">
                            <div class="card-body p-3">
                                <p class="text-muted fs-11 mb-1 fw-bold text-success">Total Debits</p>
                                <h5 class="mb-0 font-monospace text-success">+{{ number_format($ledgerData['total_debit'], 2) }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-none border border-200">
                            <div class="card-body p-3">
                                <p class="text-muted fs-11 mb-1 fw-bold text-danger">Total Credits</p>
                                <h5 class="mb-0 font-monospace text-danger">-{{ number_format($ledgerData['total_credit'], 2) }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-none border border-200">
                            <div class="card-body p-3">
                                <p class="text-muted fs-11 mb-1 fw-bold text-primary">Closing Balance</p>
                                <h5 class="mb-0 font-monospace text-primary">{{ number_format($ledgerData['closing_balance'], 2) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Controls Toolbar -->
                <div class="px-3 py-2 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 d-print-none">
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="max-width: 280px;">
                            <span class="input-group-text bg-white"><span class="fas fa-search text-muted"></span></span>
                            <input wire:model.live.debounce.300ms="entrySearch" 
                                   type="search" 
                                   class="form-control" 
                                   placeholder="Filter voucher, ref, narration...">
                        </div>
                        @if($entrySearch)
                            <button wire:click="$set('entrySearch', '')" class="btn btn-link btn-sm text-muted p-0 fs-11">Clear</button>
                        @endif
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <label class="fs-11 text-muted mb-0">Show:</label>
                        <select wire:model.live="perPage" class="form-select form-select-sm" style="width: auto;">
                            <option value="15">15 rows</option>
                            <option value="25">25 rows</option>
                            <option value="50">50 rows</option>
                            <option value="100">100 rows</option>
                            <option value="all">All rows</option>
                        </select>
                    </div>
                </div>

                <!-- Ledger Table -->
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th class="px-3" style="width: 10%;">Date</th>
                                <th style="width: 12%;">Voucher No</th>
                                <th style="width: 12%;">Reference</th>
                                <th style="width: 36%;">Narration</th>
                                <th class="text-end" style="width: 10%;">Debit</th>
                                <th class="text-end" style="width: 10%;">Credit</th>
                                <th class="text-end px-3" style="width: 10%;">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Opening Balance Row (Shown on Page 1 or if no pagination) -->
                            @if(!$paginatedEntries || $paginatedEntries->currentPage() == 1)
                                <tr class="fw-bold bg-light">
                                    <td class="px-3 font-monospace text-nowrap">{{ date('Y-m-d', strtotime($startDate)) }}</td>
                                    <td colspan="3">Opening Balance Brought Forward</td>
                                    <td class="text-end font-monospace">
                                        {{ $ledgerData['opening_balance_debit'] > 0 ? number_format($ledgerData['opening_balance_debit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end font-monospace">
                                        {{ $ledgerData['opening_balance_credit'] > 0 ? number_format($ledgerData['opening_balance_credit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end px-3 font-monospace">
                                        {{ number_format($ledgerData['opening_balance'], 2) }}
                                    </td>
                                </tr>
                            @else
                                <tr class="text-muted bg-light fs-11">
                                    <td colspan="7" class="px-3 py-1">
                                        <i class="fas fa-info-circle me-1"></i>Showing entries for page {{ $paginatedEntries->currentPage() }} (Opening balance for period: <span class="font-monospace fw-bold">{{ number_format($ledgerData['opening_balance'], 2) }}</span>)
                                    </td>
                                </tr>
                            @endif

                            <!-- Posted Entries -->
                            @if($paginatedEntries)
                                @forelse($paginatedEntries as $entry)
                                    <tr>
                                        <td class="px-3 font-monospace text-nowrap">{{ date('Y-m-d', strtotime($entry['voucher_date'])) }}</td>
                                        <td>
                                            <span class="badge badge-subtle-secondary font-monospace">{{ $entry['voucher_no'] }}</span>
                                        </td>
                                        <td>{{ $entry['reference'] ?? '—' }}</td>
                                        <td>{{ $entry['narration'] ?? '—' }}</td>
                                        <td class="text-end font-monospace text-success">
                                            {{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '—' }}
                                        </td>
                                        <td class="text-end font-monospace text-danger">
                                            {{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '—' }}
                                        </td>
                                        <td class="text-end px-3 font-monospace fw-semi-bold">
                                            {{ number_format($entry['running_balance'], 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No transaction entries found matching your query.
                                        </td>
                                    </tr>
                                @endforelse
                            @endif
                        </tbody>
                        <tfoot class="bg-200 fw-bold">
                            <tr>
                                <td colspan="4" class="text-end px-3">Closing Balances / Totals:</td>
                                <td class="text-end font-monospace text-success fs-9">+{{ number_format($ledgerData['total_debit'], 2) }}</td>
                                <td class="text-end font-monospace text-danger fs-9">-{{ number_format($ledgerData['total_credit'], 2) }}</td>
                                <td class="text-end font-monospace text-primary px-3 fs-9">{{ number_format($ledgerData['closing_balance'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination Footer -->
                @if($paginatedEntries)
                    <div class="card-footer bg-light d-flex align-items-center justify-content-between p-2 flex-wrap gap-2 d-print-none">
                        <small class="text-muted">
                            Showing {{ $paginatedEntries->firstItem() ?? 0 }}–{{ $paginatedEntries->lastItem() ?? 0 }} of {{ $totalEntriesCount }} entries
                            @if($entrySearch)
                                (filtered from {{ count($ledgerData['entries']) }} total)
                            @endif
                        </small>
                        <div>
                            {{ $paginatedEntries->links() }}
                        </div>
                    </div>
                @endif
            @else
                <div class="text-center py-5 text-muted">
                    <span class="fas fa-book fa-2x mb-2 d-block"></span>
                    Please select filters and click Search to load the ledger records.
                </div>
            @endif
        </div>
    </div>
</div>

<style>
@media print {
    .d-print-none, 
    .navbar, 
    .navbar-vertical, 
    .footer, 
    #sidebar, 
    nav {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-header, .card-footer {
        background: transparent !important;
    }
    body {
        background: #ffffff !important;
        font-size: 10pt;
    }
}
.cursor-pointer {
    cursor: pointer;
}
</style>
