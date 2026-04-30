@extends('layouts.app')
@section('title', $customer->name)
@section('page-title', $customer->name)

@section('content')
<div class="space-y-5 max-w-5xl" x-data="repaymentModal()" x-cloak>

    <div class="flex items-center justify-between">
        <a href="{{ route('customers.index') }}" class="btn-secondary text-xs">← Customers</a>
        @if($customer->outstanding_balance > 0)
        <button @click="openModal()" class="btn-primary">
            Record Repayment
        </button>
        @endif
    </div>

    <div class="grid md:grid-cols-3 gap-4">
        <div class="card p-5">
            <p class="text-slate-500 text-xs">Outstanding Debt</p>
            <p class="text-2xl font-bold {{ $customer->outstanding_balance > 0 ? 'text-red-400' : 'text-green-400' }} mt-1">
                {{ auth()->user()->shop->currency_symbol }}{{ number_format($customer->outstanding_balance, 2) }}
            </p>
        </div>
        <div class="card p-5">
            <p class="text-slate-500 text-xs">Total Spent (All Time)</p>
            <p class="text-2xl font-bold text-white mt-1">{{ auth()->user()->shop->currency_symbol }}{{ number_format($customer->totalSpent(), 2) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-slate-500 text-xs">Total Sales</p>
            <p class="text-2xl font-bold text-white mt-1">{{ $customer->sales->count() }}</p>
        </div>
    </div>

    <div class="card p-5 space-y-3">
        <h2 class="text-white font-semibold text-sm">Customer Info</h2>
        @foreach([['Phone', $customer->phone], ['Email', $customer->email], ['Address', $customer->address], ['Credit Limit', '₵' . number_format($customer->credit_limit, 2)]] as [$label, $value])
        <div class="flex justify-between py-1.5 border-b border-slate-800/60 last:border-0">
            <span class="text-slate-500 text-xs">{{ $label }}</span>
            <span class="text-slate-300 text-sm">{{ $value ?: '—' }}</span>
        </div>
        @endforeach
    </div>

    {{-- Recent sales with Pay button for unpaid --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-800 flex justify-between items-center">
            <h2 class="text-white font-semibold text-sm">Recent Sales</h2>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-wider border-b border-slate-800">
                    <th class="text-left px-5 py-2">Reference</th>
                    <th class="text-right px-3 py-2">Total</th>
                    <th class="text-center px-3 py-2">Payment Status</th>
                    <th class="text-right px-5 py-2">Date</th>
                    <th class="text-center px-3 py-2">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($recentSales as $sale)
                <tr class="hover:bg-white/[0.02]">
                    <td class="px-5 py-3 font-mono text-xs text-green-400 cursor-pointer" onclick="window.location='{{ route('sales.show', $sale) }}'">{{ $sale->reference }}</td>
                    <td class="px-3 py-3 text-right text-white font-medium cursor-pointer" onclick="window.location='{{ route('sales.show', $sale) }}'">{{ auth()->user()->shop->currency_symbol }}{{ number_format($sale->total, 2) }}</td>
                    <td class="px-3 py-3 text-center">
                        @php $pc = ['paid'=>'bg-green-500/10 text-green-400','partial'=>'bg-amber-500/10 text-amber-400','unpaid'=>'bg-red-500/10 text-red-400']; @endphp
                        <span class="badge {{ $pc[$sale->payment_status] ?? '' }}">{{ ucfirst($sale->payment_status) }}</span>
                    </td>
                    <td class="px-5 py-3 text-right text-slate-500 text-xs cursor-pointer" onclick="window.location='{{ route('sales.show', $sale) }}'">{{ $sale->created_at->format('d M Y') }}</td>
                    <td class="px-3 py-3 text-center">
                        @if($sale->payment_status !== 'paid')
                        <button @click="paySingleSale({{ $sale->id }}, {{ $sale->balance_due }}, '{{ addslashes($sale->reference) }}')"
                                class="btn-primary text-xs px-3 py-1">
                            Pay
                        </button>
                        @else
                        <span class="text-slate-600 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-8 text-center text-slate-600">No sales yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Repayment Modal (bulk) --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="card w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 space-y-4" @click.outside="closeModal()">
            <div class="flex items-center justify-between">
                <h3 class="text-white font-semibold text-lg">Record Debt Repayment</h3>
                <button @click="closeModal()" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <p class="text-slate-400 text-sm">Current balance: <span class="text-red-400 font-bold">{{ auth()->user()->shop->currency_symbol }}{{ number_format($customer->outstanding_balance, 2) }}</span></p>

            <div class="space-y-3">
                <label class="text-slate-300 text-sm font-semibold">Select Sales to Pay</label>
                <div class="space-y-2 max-h-60 overflow-y-auto border border-slate-800 rounded p-3">
                    <template x-for="sale in unpaidSales" :key="sale.id">
                        <div class="flex items-center justify-between p-3 border border-slate-700 rounded hover:bg-slate-900/50">
                            <div class="flex items-center gap-3 flex-1">
                                <input type="checkbox" :id="'sale_' + sale.id" x-model="selectedSaleIds" :value="sale.id" @change="updateSelectedSales()" class="w-4 h-4">
                                <label :for="'sale_' + sale.id" class="flex-1 cursor-pointer">
                                    <div class="text-green-400 text-sm font-mono" x-text="sale.reference"></div>
                                    <div class="text-slate-400 text-xs" x-text="'Due: ' + currency + sale.balance_due.toFixed(2) + ' of ' + currency + sale.total.toFixed(2)"></div>
                                </label>
                            </div>
                            <div class="text-right">
                                <div class="text-slate-300 font-medium" x-text="currency + sale.balance_due.toFixed(2)"></div>
                            </div>
                        </div>
                    </template>
                    <div x-show="unpaidSales.length === 0" class="text-center py-8 text-slate-500 text-sm">No unpaid sales found</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Total to Pay</label>
                    <input type="number" x-model.number="totalAmount" step="0.01" min="0" class="input text-white">
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Payment Method</label>
                    <select x-model="paymentMethod" class="input">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                </div>
            </div>

            <div class="space-y-2 pt-2" x-show="selectedSaleIds.length > 0">
                <label class="text-slate-300 text-sm font-semibold">Amount per Sale (Optional - adjust individual payments)</label>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    <template x-for="(sale, index) in selectedSales" :key="sale.id">
                        <div class="flex items-center gap-3 p-2 bg-slate-900/50 rounded">
                            <span class="text-green-400 text-sm font-mono flex-1" x-text="sale.reference"></span>
                            <input type="number" x-model.number="saleAmounts[index]" :max="sale.balance_due" step="0.01" min="0" @input="updateTotalFromSaleAmounts()" class="input w-24 text-right" :placeholder="currency + sale.balance_due.toFixed(2)">
                        </div>
                    </template>
                </div>
            </div>

            <div>
                <label class="text-slate-400 text-xs mb-1 block">Notes (Optional)</label>
                <textarea x-model="notes" class="input resize-none h-16" placeholder="Add any notes about this payment..."></textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button @click="closeModal()" class="btn-secondary flex-1">Cancel</button>
                <button @click="processRepayment()" :disabled="selectedSaleIds.length === 0 || totalAmount <= 0" class="btn-primary flex-1 disabled:opacity-50">Process Payment & Generate Receipt</button>
            </div>
        </div>
    </div>

    {{-- Single Sale Payment Modal (simple confirmation) --}}
    <div x-show="singlePayModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="card w-96 p-6 space-y-4">
            <h3 class="text-white font-semibold text-lg">Pay for Sale</h3>
            <p class="text-slate-400 text-sm">Sale: <span class="text-green-400 font-mono" x-text="singleSaleRef"></span></p>
            <p class="text-slate-400 text-sm">Balance Due: <span class="text-red-400 font-bold" x-text="currency + singleSaleBalance.toFixed(2)"></span></p>
            <div>
                <label class="text-slate-400 text-xs mb-1 block">Payment Method</label>
                <select x-model="singlePaymentMethod" class="input">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="transfer">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                    <option value="mobile_money">Mobile Money</option>
                </select>
            </div>
            <div>
                <label class="text-slate-400 text-xs mb-1 block">Amount to Pay</label>
                <input type="number" x-model.number="singleAmount" step="0.01" min="0" class="input" :max="singleSaleBalance">
            </div>
            <div class="flex gap-3 pt-2">
                <button @click="singlePayModal = false" class="btn-secondary flex-1">Cancel</button>
                <button @click="processSinglePayment()" class="btn-primary flex-1">Pay</button>
            </div>
        </div>
    </div>

</div>

<script>
function repaymentModal() {
    return {
        showModal: false,
        singlePayModal: false,
        singleSaleId: null,
        singleSaleRef: '',
        singleSaleBalance: 0,
        singlePaymentMethod: 'cash',
        singleAmount: 0,
        currency: '{{ auth()->user()->shop->currency_symbol }}',
        unpaidSales: @json($unpaidSales ?? []),
        selectedSaleIds: [],
        saleAmounts: [],
        totalAmount: 0,
        paymentMethod: 'cash',
        notes: '',

        // Computed: selected sales objects
        get selectedSales() {
            return this.unpaidSales.filter(sale => this.selectedSaleIds.includes(sale.id));
        },

        // Watch for changes to totalAmount (called via x-model and also manual changes)
        init() {
            this.$watch('totalAmount', (value) => {
                // Only distribute if totalAmount changed programmatically or by user
                if (this.selectedSaleIds.length > 0 && value !== this.getSumOfSaleAmounts()) {
                    this.distributeTotalToSales(value);
                }
            });
        },

        getSumOfSaleAmounts() {
            return this.saleAmounts.reduce((sum, amt) => sum + (amt || 0), 0);
        },

        updateTotalFromSaleAmounts() {
            this.totalAmount = this.getSumOfSaleAmounts();
        },

        distributeTotalToSales(total) {
            if (this.selectedSales.length === 0) return;
            // Distribute total proportionally to the balance due of each sale
            const balances = this.selectedSales.map(s => s.balance_due);
            const totalBalances = balances.reduce((a, b) => a + b, 0);
            if (totalBalances === 0) return;
            let remaining = total;
            const newAmounts = [];
            for (let i = 0; i < balances.length - 1; i++) {
                let amount = (balances[i] / totalBalances) * total;
                amount = Math.min(amount, balances[i]);
                amount = Math.max(0, amount);
                newAmounts.push(amount);
                remaining -= amount;
            }
            // Last sale gets the remainder (to avoid floating point gaps)
            newAmounts.push(Math.min(remaining, balances[balances.length - 1]));
            // Ensure no negative
            this.saleAmounts = newAmounts.map(v => Math.max(0, v));
        },

        openModal() {
            this.showModal = true;
            this.selectedSaleIds = [];
            this.saleAmounts = [];
            this.totalAmount = 0;
            this.paymentMethod = 'cash';
            this.notes = '';
        },

        closeModal() {
            this.showModal = false;
        },

        updateSelectedSales() {
            // When checkboxes change, reset amounts to full balance due
            this.saleAmounts = this.selectedSales.map(sale => sale.balance_due);
            this.totalAmount = this.getSumOfSaleAmounts();
        },

        paySingleSale(id, balance, ref) {
            this.singleSaleId = id;
            this.singleSaleBalance = balance;
            this.singleSaleRef = ref;
            this.singleAmount = balance;
            this.singlePaymentMethod = 'cash';
            this.singlePayModal = true;
        },

        async processSinglePayment() {
            if (!this.singleSaleId || this.singleAmount <= 0) {
                alert('Invalid amount');
                return;
            }
            if (this.singleAmount > this.singleSaleBalance) {
                alert('Amount cannot exceed balance due');
                return;
            }

            const payments = [{ sale_id: this.singleSaleId, amount: this.singleAmount }];

            try {
                const response = await fetch('{{ route('customers.repay', $customer) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        payments: payments,
                        method: this.singlePaymentMethod,
                        notes: ''
                    })
                });
                const data = await response.json();
                if (data.success) {
                    this.generateReceipt(data);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert('Error: ' + (data.message || 'Failed to process payment'));
                }
            } catch (error) {
                alert('An error occurred');
            }
        },

        async processRepayment() {
            if (this.selectedSaleIds.length === 0) {
                alert('Please select at least one sale to pay');
                return;
            }
            if (this.totalAmount <= 0) {
                alert('Total payment amount must be greater than zero');
                return;
            }

            const payments = this.selectedSales.map((sale, index) => ({
                sale_id: sale.id,
                amount: this.saleAmounts[index] || 0
            }));

            // Validate each amount
            for (let i = 0; i < payments.length; i++) {
                if (payments[i].amount <= 0) {
                    alert(`Amount for sale ${this.selectedSales[i].reference} is zero or negative`);
                    return;
                }
                if (payments[i].amount > this.selectedSales[i].balance_due) {
                    alert(`Amount for sale ${this.selectedSales[i].reference} exceeds balance due`);
                    return;
                }
            }

            try {
                const response = await fetch('{{ route('customers.repay', $customer) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        payments: payments,
                        method: this.paymentMethod,
                        notes: this.notes
                    })
                });
                const data = await response.json();
                if (data.success) {
                    this.generateReceipt(data);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert('Error: ' + (data.message || 'Failed to process payment'));
                }
            } catch (error) {
                alert('An error occurred while processing the payment');
            }
        },

        generateReceipt(data) {
            const itemsHtml = data.items.map(item => `
                <div style="display: flex; justify-content: space-between; margin: 8px 0;">
                    <span>${item.reference}</span>
                    <span>${data.currency}${parseFloat(item.amount).toFixed(2)}</span>
                </div>
            `).join('');

            const receiptContent = `
                <div style="font-family: monospace; max-width: 400px; margin: 0 auto; padding: 20px; background: white; color: black;">
                    <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">
                        <h2 style="margin: 0;">${data.shop_name}</h2>
                        <p style="margin: 5px 0; font-size: 0.9em;">${data.branch_name}</p>
                        <p style="margin: 5px 0; font-size: 0.8em;">${data.branch_phone}</p>
                        <h3>PAYMENT RECEIPT</h3>
                        <p style="margin: 5px 0; font-size: 0.9em;">Receipt #: ${data.receipt_no}</p>
                        <p style="margin: 5px 0; font-size: 0.9em;">Date: ${data.date}</p>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <p style="margin: 5px 0;"><strong>Customer:</strong> ${data.customer_name}</p>
                        <p style="margin: 5px 0;"><strong>Cashier:</strong> ${data.cashier}</p>
                    </div>

                    <div style="margin-bottom: 20px; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0;">
                        <p style="margin: 5px 0; font-weight: bold;">Payments Made:</p>
                        ${itemsHtml}
                    </div>

                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1em;">
                            <span>Total Paid:</span>
                            <span>${data.currency}${parseFloat(data.total_paid).toFixed(2)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; color: ${data.remaining_debt > 0 ? 'red' : 'green'};">
                            <span>Remaining Balance:</span>
                            <span>${data.currency}${parseFloat(data.remaining_debt).toFixed(2)}</span>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <p style="margin: 5px 0;"><strong>Payment Method:</strong> ${data.method}</p>
                    </div>

                    <div style="text-align: center; margin-top: 20px; border-top: 2px solid #000; padding-top: 10px;">
                        <p style="margin: 5px 0; font-size: 0.9em;">Thank you for your payment!</p>
                        <p style="margin: 5px 0; font-size: 0.8em;">Time: ${new Date().toLocaleTimeString()}</p>
                    </div>
                </div>
            `;

            const printWindow = window.open('', '_blank', 'width=600,height=800');
            printWindow.document.write(receiptContent);
            printWindow.document.close();
            printWindow.print();
        }
    };
}
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection