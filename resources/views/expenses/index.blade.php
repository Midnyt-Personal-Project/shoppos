@extends('layouts.app')
@section('title', 'Expenses')
@section('page-title', 'Expenses')

@section('content')
    <div class="space-y-5" id="expensesApp">

        <div class="flex items-center justify-between">
            <div class="card px-4 py-3">
                <span class="text-slate-400 text-xs">Total in period:</span>
                <span class="text-white font-bold ml-2">₵{{ number_format($totalAmount, 2) }}</span>
            </div>
            <button id="openAddModal" class="btn-primary">+ Add Expense</button>
        </div>

        <form method="GET" class="flex gap-3 flex-wrap items-center">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="input w-40">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="input w-40">
            <select name="category" class="input w-44">
                <option value="">All Categories</option>
                <option value="uncategorized" @selected(request('category') === 'uncategorized')>Uncategorized</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-secondary">Filter</button>

            <div class="flex gap-2 items-center ml-auto">
                <span class="text-slate-400 text-xs">Export:</span>
                <a href="{{ route('expenses.export.xlsx', request()->query()) }}"
                    class="text-green-400 hover:text-green-300 text-sm transition-colors">
                    XLSX
                </a>
                <span class="text-slate-600">|</span>
                <a href="{{ route('expenses.export.csv', request()->query()) }}"
                    class="text-blue-400 hover:text-blue-300 text-sm transition-colors">
                    CSV
                </a>
            </div>
        </form>


        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-500 text-xs uppercase tracking-wider">
                        <th class="text-left px-5 py-3">Description</th>
                        <th class="text-left px-3 py-3">Category</th>
                        <th class="text-left px-3 py-3">By</th>
                        <th class="text-right px-3 py-3">Amount</th>
                        <th class="text-right px-3 py-3">Receipt</th>
                        <th class="text-center px-3 py-3">View</th>
                        <th class="text-right px-5 py-3">Date</th>
                        @if (auth()->user()->isAdmin() || auth()->user()->isManager())
                            <th class="text-center px-3 py-3">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($expenses as $expense)
                        <tr class="hover:bg-white/[0.02] cursor-pointer view-row"
                            data-expense='{{ json_encode(
                                [
                                    'id' => $expense->id,
                                    'title' => $expense->title,
                                    'category' => $expense->category,
                                    'amount' => $expense->amount,
                                    'expense_date' => $expense->expense_date->format('d M Y'),
                                    'notes' => $expense->notes,
                                    'user' => $expense->user->name,
                                    'receipt_path' => $expense->receipt_path,
                                ],
                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP,
                            ) }}'>
                            <td class="px-5 py-3">
                                <p class="text-white">{{ $expense->title }}</p>
                                @if ($expense->notes)
                                    <p class="text-slate-600 text-xs">{{ $expense->notes }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if ($expense->category)
                                    <span
                                        class="badge bg-slate-700 text-slate-300">{{ ucfirst($expense->category) }}</span>
                                @else
                                    <span class="text-slate-500 text-xs italic">Uncategorized</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-400">{{ $expense->user->name }}</td>
                            <td class="px-3 py-3 text-right text-amber-400 font-medium">
                                ₵{{ number_format($expense->amount, 2) }}</td>
                            <td class="px-3 py-3 text-right">
                                @if ($expense->receipt_url)
                                    <a href="{{ $expense->receipt_url }}" target="_blank"
                                        class="text-blue-400 hover:text-blue-300 transition-colors inline-flex items-center gap-1"
                                        title="View receipt">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('expenses.download-receipt', $expense) }}"
                                        class="text-slate-500 hover:text-green-400 transition-colors inline-flex items-center gap-1 ml-2"
                                        title="Download receipt">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-slate-600 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <button class="text-slate-500 hover:text-blue-400 transition-colors view-expense-btn"
                                    title="View details">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-slate-500 text-xs">{{ $expense->expense_date->format('d M Y') }}</span>
                            </td>
                            @if (auth()->user()->isAdmin() || auth()->user()->isManager())
                                <td class="px-3 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            class="text-slate-500 hover:text-blue-400 transition-colors edit-expense-btn"
                                            data-expense='{{ json_encode(
                                                [
                                                    'id' => $expense->id,
                                                    'title' => $expense->title,
                                                    'category' => $expense->category,
                                                    'amount' => $expense->amount,
                                                    'expense_date' => $expense->expense_date->format('Y-m-d'),
                                                    'notes' => $expense->notes,
                                                    'receipt_path' => $expense->receipt_path,
                                                ],
                                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP,
                                            ) }}'
                                            title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                                            onsubmit="return confirm('Delete this expense?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="text-slate-700 hover:text-red-400 transition-colors"
                                                title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isAdmin() || auth()->user()->isManager() ? '8' : '7' }}"
                                class="px-5 py-12 text-center text-slate-600">No expenses recorded</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 py-3 border-t border-slate-800">{{ $expenses->links() }}</div>
        </div>

        {{-- ── Add Modal ──────────────────────────────────────────── --}}
        <div id="addModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60">
            <div class="card w-96 p-6">
                <h3 class="text-white font-semibold mb-4">Record Expense</h3>
                <form method="POST" action="{{ route('expenses.store') }}" class="space-y-3" id="addForm"
                    enctype="multipart/form-data">
                    @csrf
                    <div><label class="text-slate-400 text-xs mb-1 block">Title *</label><input type="text"
                            name="title" required class="input"></div>

                    @if (auth()->user()->isAdmin() || auth()->user()->isManager())
                        <div>
                            <label class="text-slate-400 text-xs mb-1 block">Category</label>
                            <div class="flex gap-2">
                                <select name="category" id="addCategorySelect" class="input flex-1">
                                    <option value="">— Uncategorized —</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                                <button type="button" id="addCategoryBtn" class="btn-secondary shrink-0"
                                    title="Add new category">+</button>
                            </div>
                        </div>
                    @endif

                    <div><label class="text-slate-400 text-xs mb-1 block">Amount (₵) *</label><input type="number"
                            name="amount" required step="0.01" min="0.01" class="input"></div>
                    <div><label class="text-slate-400 text-xs mb-1 block">Date *</label><input type="date"
                            name="expense_date" required value="{{ today()->toDateString() }}" class="input"></div>
                    <div><label class="text-slate-400 text-xs mb-1 block">Notes</label>
                        <textarea name="notes" rows="2" class="input resize-none"></textarea>
                    </div>

                    {{-- Receipt upload --}}
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Receipt (image)</label>
                        <input type="file" name="receipt" accept="image/*" class="input p-1.5" id="addReceiptInput">
                        <div id="addReceiptPreview" class="mt-2 hidden">
                            <img src="#" alt="Receipt preview" class="max-h-32 rounded border border-slate-700">
                        </div>
                    </div>

                    <div class="flex gap-3 mt-2">
                        <button type="button" class="btn-secondary flex-1 close-modal">Cancel</button>
                        <button type="submit" class="btn-primary flex-1">Save</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Edit Modal ──────────────────────────────────────────── --}}
        <div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60">
            <div class="card w-96 p-6">
                <h3 class="text-white font-semibold mb-4">Edit Expense</h3>
                <form method="POST" id="editForm" class="space-y-3" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <input type="hidden" name="id" id="editExpenseId">
                    <div><label class="text-slate-400 text-xs mb-1 block">Title *</label>
                        <input type="text" name="title" id="editTitle" required class="input">
                    </div>

                    @if (auth()->user()->isAdmin() || auth()->user()->isManager())
                        <div>
                            <label class="text-slate-400 text-xs mb-1 block">Category</label>
                            <div class="flex gap-2">
                                <select name="category" id="editCategorySelect" class="input flex-1">
                                    <option value="">— Uncategorized —</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                                <button type="button" id="editAddCategoryBtn" class="btn-secondary shrink-0"
                                    title="Add new category">+</button>
                            </div>
                        </div>
                    @endif

                    <div><label class="text-slate-400 text-xs mb-1 block">Amount (₵) *</label>
                        <input type="number" name="amount" id="editAmount" required step="0.01" min="0.01"
                            class="input">
                    </div>
                    <div><label class="text-slate-400 text-xs mb-1 block">Date *</label>
                        <input type="date" name="expense_date" id="editDate" required class="input">
                    </div>
                    <div><label class="text-slate-400 text-xs mb-1 block">Notes</label>
                        <textarea name="notes" id="editNotes" rows="2" class="input resize-none"></textarea>
                    </div>

                    {{-- Receipt upload with preview --}}
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Receipt (image)</label>
                        <input type="file" name="receipt" accept="image/*" class="input p-1.5"
                            id="editReceiptInput">
                        <div id="editReceiptPreview" class="mt-2 hidden">
                            <img src="#" alt="Receipt preview" class="max-h-32 rounded border border-slate-700">
                        </div>
                        <div id="editCurrentReceipt" class="mt-2 hidden">
                            <p class="text-slate-500 text-xs">Current receipt:</p>
                            <img src="#" id="editCurrentReceiptImg"
                                class="max-h-32 rounded border border-slate-700">
                        </div>
                    </div>

                    <div class="flex gap-3 mt-2">
                        <button type="button" class="btn-secondary flex-1 close-modal">Cancel</button>
                        <button type="submit" class="btn-primary flex-1">Update</button>
                    </div>
                </form>
            </div>
        </div>


        <div id="viewModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60">
            <div class="card w-96 p-6">
                <h3 class="text-white font-semibold mb-4">Expense Details</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-slate-400 text-xs block">Description</span>
                        <p class="text-white font-medium" id="viewTitle">—</p>
                    </div>
                    <div>
                        <span class="text-slate-400 text-xs block">Category</span>
                        <p class="text-slate-300" id="viewCategory">—</p>
                    </div>
                    <div>
                        <span class="text-slate-400 text-xs block">Amount</span>
                        <p class="text-amber-400 font-medium" id="viewAmount">—</p>
                    </div>
                    <div>
                        <span class="text-slate-400 text-xs block">Date</span>
                        <p class="text-slate-300" id="viewDate">—</p>
                    </div>
                    <div>
                        <span class="text-slate-400 text-xs block">Added by</span>
                        <p class="text-slate-300" id="viewUser">—</p>
                    </div>
                    <div>
                        <span class="text-slate-400 text-xs block">Comment</span>
                        <p class="text-slate-300 whitespace-pre-wrap" id="viewNotes">—</p>
                    </div>
                    <div>
                        <span class="text-slate-400 text-xs block">Receipt</span>
                        <div id="viewReceiptContainer">
                            <p class="text-slate-500 text-xs" id="viewNoReceipt">No receipt attached</p>
                            <div id="viewReceiptPreview" class="hidden mt-1">
                                <img src="#" alt="Receipt" id="viewReceiptImg"
                                    class="max-h-48 rounded border border-slate-700">
                                <div class="mt-1 flex gap-2">
                                    <a href="#" target="_blank" id="viewReceiptLink"
                                        class="text-blue-400 hover:text-blue-300 text-xs">Open in new tab</a>
                                    <a href="#" id="viewReceiptDownload"
                                        class="text-green-400 hover:text-green-300 text-xs">Download</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-4">
                    <button type="button" class="btn-secondary flex-1 close-modal">Close</button>
                </div>
            </div>
        </div>

    </div>

    <script>
        (function() {
            // ── Elements ──────────────────────────────────────────────────────────
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            const viewModal = document.getElementById('viewModal');
            const openAddBtn = document.getElementById('openAddModal');
            const editForm = document.getElementById('editForm');

            // ── Open Add Modal ───────────────────────────────────────────────────
            openAddBtn.addEventListener('click', function() {
                document.getElementById('addForm').reset();
                document.getElementById('addReceiptPreview').classList.add('hidden');
                addModal.classList.remove('hidden');
            });

            // ── Close modals (reuse close-modal class) ─────────────────────────
            document.querySelectorAll('.close-modal').forEach(btn => {
                btn.addEventListener('click', function() {
                    addModal.classList.add('hidden');
                    editModal.classList.add('hidden');
                    viewModal.classList.add('hidden');
                });
            });

            // ── Close on outside click ───────────────────────────────────────────
            document.addEventListener('click', function(e) {
                const modal = e.target.closest('.fixed.inset-0');
                if (modal && !e.target.closest('.card')) {
                    modal.classList.add('hidden');
                }
            });

            // ── Open Edit Modal ──────────────────────────────────────────────────
            document.querySelectorAll('.edit-expense-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const expense = JSON.parse(this.dataset.expense);
                    document.getElementById('editExpenseId').value = expense.id;
                    document.getElementById('editTitle').value = expense.title;
                    document.getElementById('editAmount').value = expense.amount;
                    document.getElementById('editDate').value = expense.expense_date;
                    document.getElementById('editNotes').value = expense.notes || '';

                    const catSelect = document.getElementById('editCategorySelect');
                    if (catSelect) {
                        catSelect.value = expense.category || '';
                    }

                    const currentReceiptDiv = document.getElementById('editCurrentReceipt');
                    const currentImg = document.getElementById('editCurrentReceiptImg');
                    if (expense.receipt_path) {
                        const url = '/storage/' + expense.receipt_path;
                        currentImg.src = url;
                        currentReceiptDiv.classList.remove('hidden');
                    } else {
                        currentReceiptDiv.classList.add('hidden');
                    }

                    document.getElementById('editReceiptInput').value = '';
                    document.getElementById('editReceiptPreview').classList.add('hidden');

                    editForm.action = '/expenses/' + expense.id;
                    editModal.classList.remove('hidden');
                });
            });

            // ── Open View Modal ──────────────────────────────────────────────────
            function openViewModal(expense) {
                document.getElementById('viewTitle').textContent = expense.title || '—';
                document.getElementById('viewCategory').textContent = expense.category || 'Uncategorized';
                document.getElementById('viewAmount').textContent = '₵' + parseFloat(expense.amount).toFixed(2);
                document.getElementById('viewDate').textContent = expense.expense_date || '—';
                document.getElementById('viewUser').textContent = expense.user || '—';
                document.getElementById('viewNotes').textContent = expense.notes || 'No notes';

                const previewDiv = document.getElementById('viewReceiptPreview');
                const noReceipt = document.getElementById('viewNoReceipt');
                const img = document.getElementById('viewReceiptImg');
                const link = document.getElementById('viewReceiptLink');
                const download = document.getElementById('viewReceiptDownload');

                if (expense.receipt_path) {
                    const url = '/storage/' + expense.receipt_path;
                    img.src = url;
                    link.href = url;
                    download.href = url;
                    previewDiv.classList.remove('hidden');
                    noReceipt.classList.add('hidden');
                } else {
                    previewDiv.classList.add('hidden');
                    noReceipt.classList.remove('hidden');
                }

                viewModal.classList.remove('hidden');
            }

            // Click on view button
            document.querySelectorAll('.view-expense-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const row = this.closest('tr');
                    if (row) {
                        const expense = JSON.parse(row.dataset.expense);
                        openViewModal(expense);
                    }
                });
            });

            // Click on entire row (except buttons/links/forms)
            document.querySelectorAll('tbody tr.view-row').forEach(row => {
                row.addEventListener('click', function(e) {
                    if (e.target.closest('button, a, form, input')) return;
                    const expense = JSON.parse(this.dataset.expense);
                    openViewModal(expense);
                });
            });

            // ── Add Category ─────────────────────────────────────────────────────
            async function addCategory(selectElementId) {
                const name = prompt('Enter new expense category name:');
                if (!name || !name.trim()) return;

                try {
                    const res = await fetch('{{ route('expense-categories.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify({
                            name: name.trim()
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        const selects = ['addCategorySelect', 'editCategorySelect'];
                        selects.forEach(id => {
                            const select = document.getElementById(id);
                            if (select) {
                                const opt = document.createElement('option');
                                opt.value = data.name;
                                opt.text = data.name;
                                select.appendChild(opt);
                            }
                        });
                        if (selectElementId) {
                            const activeSelect = document.getElementById(selectElementId);
                            if (activeSelect) activeSelect.value = data.name;
                        }
                    } else {
                        alert(data.message || 'Failed to add category.');
                    }
                } catch (err) {
                    alert('Network error: ' + err.message);
                }
            }

            document.getElementById('addCategoryBtn')?.addEventListener('click', function() {
                addCategory('addCategorySelect');
            });
            document.getElementById('editAddCategoryBtn')?.addEventListener('click', function() {
                addCategory('editCategorySelect');
            });

            // ── Image preview for Add modal ────────────────────────────────────
            document.getElementById('addReceiptInput')?.addEventListener('change', function(e) {
                const previewDiv = document.getElementById('addReceiptPreview');
                const img = previewDiv.querySelector('img');
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        img.src = ev.target.result;
                        previewDiv.classList.remove('hidden');
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    previewDiv.classList.add('hidden');
                }
            });

            // ── Image preview for Edit modal ────────────────────────────────────
            document.getElementById('editReceiptInput')?.addEventListener('change', function(e) {
                const previewDiv = document.getElementById('editReceiptPreview');
                const img = previewDiv.querySelector('img');
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        img.src = ev.target.result;
                        previewDiv.classList.remove('hidden');
                        document.getElementById('editCurrentReceipt').classList.add('hidden');
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    previewDiv.classList.add('hidden');
                }
            });

        })();
    </script>
@endsection
