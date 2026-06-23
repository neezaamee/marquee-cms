<div>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border border-200">
                <div class="card-header bg-light d-flex align-items-center">
                    <h5 class="mb-0"><span class="fas fa-cog me-2 text-primary"></span>Inventory Settings & Account Mappings</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
                            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                            <p class="mb-0 flex-1">{{ session('success') }}</p>
                            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form wire:submit.prevent="save">
                        <div class="mb-4 bg-light border p-3 rounded">
                            <h6 class="fw-bold mb-2">Double-Entry Accounting Mappings</h6>
                            <p class="text-muted fs-11 mb-0">Configure the ledger accounts that should be used when purchase transactions (posted invoices and returns) generate automated journal entry records.</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="asset-account">Inventory Asset Account *</label>
                            <select wire:model="inventory_asset_account_id" class="form-select @error('inventory_asset_account_id') is-invalid @enderror" id="asset-account">
                                <option value="">Select Account</option>
                                @foreach($accounts->where('nature', 'Asset') as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->account_code }} - {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            @error('inventory_asset_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted d-block mt-1">Asset ledger account representing items currently in storage/inventory (e.g. 1004 - Inventory).</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="payable-account">Accounts Payable Account *</label>
                            <select wire:model="accounts_payable_account_id" class="form-select @error('accounts_payable_account_id') is-invalid @enderror" id="payable-account">
                                <option value="">Select Account</option>
                                @foreach($accounts->where('nature', 'Liability') as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->account_code }} - {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            @error('accounts_payable_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted d-block mt-1">Liability ledger account representing outstanding amounts payable to suppliers (e.g. 2001 - Accounts Payable).</small>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-falcon-primary btn-sm px-4">
                                <span class="fas fa-save me-1"></span>Save Mappings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
