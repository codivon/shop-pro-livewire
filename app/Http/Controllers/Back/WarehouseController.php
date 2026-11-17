<?php

namespace App\Http\Controllers\Back;

use App\Exports\WarehouseExport;
use App\Exports\WarehouseProductsExport;
use App\Http\Controllers\Controller;
use App\Models\AttributeGroup;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\OrderItem;
use App\Models\Province;
use App\Models\Seller;
use App\Models\Warehouse;
use App\Models\Price;
use App\Models\StockMovement;
use App\Models\Product;
use App\Services\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Morilog\Jalali\Jalalian;
use function Termwind\render;

class WarehouseController extends Controller
{
    protected $stockService;

    
public function __construct(StockMovementService $stockService)
    {
        $this->stockService = $stockService;
    }

public function export(Request $request, Warehouse $warehouse)
    {
        $format = $request->get('format', 'excel');

        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';
        $warehouseName = $warehouse->name ?? $warehouse->code ?? "#{$warehouse->id}";

        // جمع‌آوری اطلاعات فیلترها برای لاگ
        $filters = [];
        if ($request->stock_status && $request->stock_status != 'all') {
            $filters['stock_status'] = $request->stock_status == 'in_stock' ? 'موجود' : 'ناموجود';
        }
        if ($request->category_id) {
            $category = \App\Models\Category::find($request->category_id);
            $filters['category'] = $category->title ?? $category->name ?? "#{$request->category_id}";
        }
        if ($request->brand_id) {
            $brand = \App\Models\Brand::find($request->brand_id);
            $filters['brand'] = $brand->name ?? "#{$request->brand_id}";
        }

        $query = Product::whereHas('prices', function ($q) use ($warehouse) {
            $q->where('warehouse_id', $warehouse->id);
        })
            ->with(['prices' => function ($q) use ($warehouse) {
                $q->where('warehouse_id', $warehouse->id);
            }, 'category', 'brand']);

        // فیلترها
        if ($request->stock_status && $request->stock_status != 'all') {
            if ($request->stock_status == 'in_stock') {
                $query->whereHas('prices', function ($q) use ($warehouse) {
                    $q->where('warehouse_id', $warehouse->id)->where('stock', '>', 0);
                });
            } elseif ($request->stock_status == 'out_of_stock') {
                $query->whereHas('prices', function ($q) use ($warehouse) {
                    $q->where('warehouse_id', $warehouse->id)->where('stock', 0);
                });
            }
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        $products = $query->get();
        $productsCount = $products->count();

        $formatText = $format == 'pdf' ? 'PDF' : 'Excel';

        $filterText = '';
        if (!empty($filters)) {
            $filterParts = [];
            foreach ($filters as $key => $value) {
                $filterParts[] = "{$key}: {$value}";
            }
            $filterText = ' با فیلترهای ' . implode('، ', $filterParts);
        }

        $logProperties = [
            'action' => 'خروجی Excel را دریافت کرد',
            'format' => $format,
            'filters' => $filters,
            'products_count' => $productsCount,
            'warehouse_name' => $warehouseName,
            'warehouse_id' => $warehouse->id,
            'ip' => $request->ip()
        ];

        if (!empty($filters)) {
            $logProperties['attributes'] = [
                'فرمت' => $formatText,
                'تعداد محصولات' => $productsCount,
                'فیلترها' => $filterText,
            ];
        } else {
            $logProperties['attributes'] = [
                'فرمت' => $formatText,
                'تعداد محصولات' => $productsCount,
            ];
        }

        $logMessage = "مدیر {$adminName} خروجی {$formatText} از محصولات انبار «{$warehouseName}» را دریافت کرد";
        $logMessage .= " (تعداد: {$productsCount} محصول){$filterText}";

        if ($format == 'pdf') {
            $pdf = Pdf::loadView('back.warehouses.exports.pdf', compact('warehouse', 'products'));

            activity()
                ->performedOn($warehouse)
                ->event('export')
                ->causedBy(auth('adminPanel')->user())
                ->withProperties($logProperties)
                ->log($logMessage);

            return $pdf->download("warehouse_{$warehouse->id}_products.pdf");
        }

        activity()
            ->performedOn($warehouse)
            ->event('export')
            ->causedBy(auth('adminPanel')->user())
            ->withProperties($logProperties)
            ->log($logMessage);

        return Excel::download(new WarehouseExport($warehouse, $products), "warehouse_{$warehouse->id}_products.xlsx");
    }

public function stockHistory(Warehouse $warehouse, Product $product, Request $request)
    {
        $this->authorize('warehouses.show');

        // گرفتن همه قیمت‌های این محصول در این انبار
        $prices = Price::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->with('attributes')
            ->get();

        // گرفتن تاریخچه حرکات
        $query = StockMovement::where('warehouse_id', $warehouse->id)
            ->whereIn('price_id', $prices->pluck('id'))
            ->with(['price.product', 'price.attributes'])
            ->orderBy('created_at', 'desc');

        // فیلتر نوع حرکت
        if ($request->movement_type && $request->movement_type != 'all') {
            $query->where('type', $request->movement_type);
        }

        // فیلتر تنوع
        if ($request->variation_id && $request->variation_id != 'all') {
            $query->where('price_id', $request->variation_id);
        }

        // فیلتر تاریخ
        if ($request->date_from) {
            $dateFrom = convertPersianToEnglish($request->date_from);
            $dateFrom = \Morilog\Jalali\Jalalian::fromFormat('Y-m-d', $dateFrom)->toCarbon()->startOfDay();
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($request->date_to) {
            $dateTo = convertPersianToEnglish($request->date_to);
            $dateTo = \Morilog\Jalali\Jalalian::fromFormat('Y-m-d', $dateTo)->toCarbon()->endOfDay();
            $query->where('created_at', '<=', $dateTo);
        }

        $stockHistory = $query->paginate(50);

        // آمار کلی
        $totalStock = $prices->sum('stock');
        $totalReserved = $prices->sum('reserved_stock');
        $totalSold = $prices->sum('sold_count');
        $variationsCount = $prices->count();

        // اگر درخواست AJAX است
        if ($request->ajax()) {
            $html = view('back.warehouses.partials.stock-history-content', compact(
                'product', 'warehouse', 'prices', 'stockHistory',
                'totalStock', 'totalReserved', 'totalSold', 'variationsCount'
            ))->render();

            $pagination = $stockHistory->links()->render();

            return response()->json([
                'html' => $html,
                'pagination' => $pagination,
                'product_title' => $product->title,
                'product_id' => $product->id
            ]);
        }

        // خروجی اکسل
        if ($request->export == 'excel') {
            return $this->exportStockHistory($product, $stockHistory);
        }

        return view('back.warehouses.partials.stock-history', compact(
            'warehouse', 'product', 'prices', 'stockHistory',
            'totalStock', 'totalReserved', 'totalSold', 'variationsCount'
        ));
    }

private function exportStockHistory($product, $stockHistory)
    {
        $adminName = auth('adminPanel')->user()->full_name ?? auth()->user()->name ?? 'مدیر';
        $productTitle = $product->title ?? $product->name ?? "#{$product->id}";
        $recordsCount = count($stockHistory);

        // ========== ثبت لاگ ==========
        activity()
            ->performedOn($product)
            ->causedBy(auth()->user())
            ->event('ecport')
            ->withProperties([
                'action' => 'export_stock_history',
                'attributes' => [
                    'نام محصول' => $productTitle,
                    'تعداد رکوردها' => $recordsCount,
                    'فرمت' => 'CSV',
                ],
                'ip' => request()->ip()
            ])
            ->log("مدیر {$adminName} تاریخچه موجودی محصول «{$productTitle}» را خروجی گرفت");

        // روش ساده: خروجی CSV
        $filename = "stock_history_product_{$product->id}_" . date('Y-m-d') . ".csv";
        $handle = fopen('php://output', 'w');

        // هدرها
        fputcsv($handle, ['تاریخ', 'نوع', 'تنوع', 'تعداد', 'موجودی قبل', 'موجودی بعد', 'توضیحات', 'اپراتور']);

        foreach ($stockHistory as $movement) {
            fputcsv($handle, [
                jdate($movement->created_at)->format('Y-m-d H:i:s'),
                $this->translateMovementType($movement->type),
                $movement->price_id,
                $movement->quantity,
                $movement->before_stock,
                $movement->after_stock,
                $movement->description,
                $this->getOperatorName($movement)
            ]);
        }

        fclose($handle);

        return response()->make('', 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

private function translateMovementType($type)
    {
        $types = [
            'in' => 'ورود',
            'out' => 'خروج',
            'adjustment' => 'تنظیم موجودی',
            'reserve' => 'رزرو',
            'unreserve' => 'لغو رزرو',
        ];

        return $types[$type] ?? $type;
    }

private function getOperatorName($movement)
    {
        $operatorType = $movement->operator_type;
        $operatorId = $movement->operator_id;

        if (!$operatorId) {
            return 'سیستم';
        }

        switch ($operatorType) {
            case 'admin':
                $admin = \App\Models\Admin::find($operatorId);
                return "مدیر - " . ($admin->full_name ?? $admin->name ?? "#{$operatorId}");
            case 'user':
                $user = \App\Models\User::find($operatorId);
                return "کاربر - " . ($user->full_name ?? $user->name ?? "#{$operatorId}");
            case 'seller':
                $seller = \App\Models\Seller::find($operatorId);
                return "فروشنده - " . ($seller->full_name ?? $seller->name ?? "#{$operatorId}");
            default:
                return "{$operatorType} - {$operatorId}";
        }
    }
}
