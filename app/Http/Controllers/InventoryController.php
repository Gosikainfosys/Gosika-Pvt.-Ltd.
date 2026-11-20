<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Supplier;
use App\Models\AuditLog;
use App\Services\CorporateExcelReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->filteredItemsQuery($request)
            ->with(['transactions' => fn ($query) => $query->latest()->limit(5)])
            ->latest('id')
            ->paginate(10)
            ->withQueryString();
        $suppliers = Supplier::where('status', 'active')->select('id', 'name')->get();

        $stats = [
            'total_items' => InventoryItem::count(),
            'total_stock_value' => (float) InventoryItem::selectRaw('SUM(quantity * unit_price) as total')->value('total'),
            'low_stock_count' => InventoryItem::whereColumn('quantity', '<=', 'min_stock_threshold')->count(),
        ];

        return Inertia::render('Office/Inventory/Index', [
            'items' => $items,
            'suppliers' => $suppliers,
            'stats' => $stats,
            'filters' => $request->only(['category', 'low_stock', 'search']),
        ]);
    }

    public function downloadXlsx(Request $request, CorporateExcelReportService $reportService)
    {
        $items = $this->filteredItemsQuery($request)->latest('id')->get();
        $rows = $items->map(fn (InventoryItem $item) => [
            $item->item_code,
            $item->name,
            $item->category,
            $item->quantity,
            $item->unit,
            $item->min_stock_threshold,
            (float) $item->unit_price,
            $item->quantity * (float) $item->unit_price,
            $item->supplier->name ?? null,
            $item->location,
        ])->all();

        $metadata = [];
        if ($request->filled('category')) {
            $metadata['Category'] = $request->input('category');
        }
        if ($request->boolean('low_stock')) {
            $metadata['Stock Filter'] = 'Low stock only';
        }
        if ($request->filled('search')) {
            $metadata['Search'] = $request->input('search');
        }

        AuditLog::log('export', 'inventory', null, 'Exported inventory report to XLSX');

        return $reportService->download([
            'filename' => 'inventory_report.xlsx',
            'title' => 'Inventory & Stock Valuation Report',
            'metadata' => $metadata,
            'columns' => [
                ['label' => 'Item Code', 'width' => 17],
                ['label' => 'Item Name', 'width' => 26],
                ['label' => 'Category', 'width' => 20],
                ['label' => 'Quantity', 'format' => 'number', 'width' => 14],
                ['label' => 'Unit', 'width' => 12],
                ['label' => 'Minimum Stock', 'format' => 'number', 'width' => 16],
                ['label' => 'Unit Price', 'format' => 'currency', 'width' => 17],
                ['label' => 'Stock Valuation', 'format' => 'currency', 'width' => 19],
                ['label' => 'Supplier', 'width' => 24],
                ['label' => 'Location', 'width' => 23],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Inventory Items', 'value' => $items->count(), 'format' => 'number'],
                ['label' => 'Stock Valuation', 'value' => (float) $items->sum(fn (InventoryItem $item) => $item->quantity * (float) $item->unit_price), 'format' => 'currency'],
                ['label' => 'Low Stock Items', 'value' => $items->filter(fn (InventoryItem $item) => $item->isLowStock())->count(), 'format' => 'number'],
            ],
            'totals' => ['label' => 'TOTAL STOCK VALUATION', 'formulas' => [7 => 'sum']],
            'orientation' => 'landscape',
        ]);
    }

    private function filteredItemsQuery(Request $request)
    {
        $query = InventoryItem::with('supplier');
        $category = $request->input('category');
        $search = $request->input('search');

        if ($category) {
            $query->where('category', $category);
        }
        if ($request->boolean('low_stock')) {
            $query->whereColumn('quantity', '<=', 'min_stock_threshold');
        }
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('item_code', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'min_stock_threshold' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'location' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('inventory', 'public');
        }

        $lastId = InventoryItem::max('id') ?? 0;
        $validated['item_code'] = 'GSK-INV-' . str_pad($lastId + 101, 3, '0', STR_PAD_LEFT);

        $item = InventoryItem::create($validated);

        if ($item->quantity > 0) {
            InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'type' => 'in',
                'quantity' => $item->quantity,
                'balance_after' => $item->quantity,
                'date' => Carbon::today()->format('Y-m-d'),
                'notes' => 'Opening stock initialization',
                'logged_by' => auth()->id(),
            ]);
        }

        AuditLog::log('create', 'inventory', $item->id, "Created inventory item: {$item->name} ({$item->item_code})");

        return back()->with('success', 'Inventory item added successfully.');
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'min_stock_threshold' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'location' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
        ]);

        $oldImagePath = $inventoryItem->image_path;
        $previousQuantity = $inventoryItem->quantity;

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('inventory', 'public');
        }

        DB::transaction(function () use ($validated, $inventoryItem, $previousQuantity) {
            $inventoryItem->update($validated);

            if ($inventoryItem->quantity !== $previousQuantity) {
                InventoryTransaction::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'type' => 'adjustment',
                    'quantity' => $inventoryItem->quantity,
                    'balance_after' => $inventoryItem->quantity,
                    'date' => Carbon::today()->format('Y-m-d'),
                    'notes' => 'Quantity corrected while editing inventory item',
                    'logged_by' => auth()->id(),
                ]);
            }

            AuditLog::log('update', 'inventory', $inventoryItem->id, "Updated inventory item: {$inventoryItem->name}");
        });

        if (isset($validated['image_path']) && $oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return back()->with('success', 'Inventory item updated successfully.');
    }

    public function stockAdjustment(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:in,out,adjustment'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'reference_no' => ['nullable', 'string'],
        ]);

        $currentQty = $inventoryItem->quantity;
        if ($validated['type'] === 'in') {
            $newQty = $currentQty + $validated['quantity'];
        } elseif ($validated['type'] === 'out') {
            if ($currentQty < $validated['quantity']) {
                return back()->with('error', 'Cannot deduct more than available inventory.');
            }
            $newQty = $currentQty - $validated['quantity'];
        } else {
            // direct adjustment
            $newQty = $validated['quantity'];
        }

        $inventoryItem->update(['quantity' => $newQty]);

        InventoryTransaction::create([
            'inventory_item_id' => $inventoryItem->id,
            'type' => $validated['type'],
            'quantity' => $validated['quantity'],
            'balance_after' => $newQty,
            'date' => Carbon::today()->format('Y-m-d'),
            'reference_no' => $validated['reference_no'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'logged_by' => auth()->id(),
        ]);

        AuditLog::log('update', 'inventory', $inventoryItem->id, "Adjusted inventory for {$inventoryItem->name} (New Balance: {$newQty})");

        return back()->with('success', "Stock updated. New balance: {$newQty} {$inventoryItem->unit}");
    }
}
