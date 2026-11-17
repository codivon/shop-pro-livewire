<?php

namespace App\Http\Controllers\Back;

use App\Events\OrderCreated;
use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Back\Order\OrderStoreRequest;
use App\Models\Carrier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\Province;
use App\Models\Seller;
use App\Models\SizeType;
use App\Models\User;
use App\Models\WalletHistory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    
public function __construct()
    {
        $this->authorizeResource(Order::class, 'order');
    }

public function printAllShippingForms(Request $request)
    {
        $this->authorize('orders.view');

        $orders = Order::paid()->whereIn('id', $request->ids)->get();
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';
        $orderIds = implode('، ', $request->ids);

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('export')
            ->withProperties([
                'action' => 'print_shipping_forms',
                'order_ids' => $request->ids,
                'orders_count' => $orders->count(),
                'format' => 'shipping_forms',
                'ip' => request()->ip()
            ])
            ->log("مدیر {$adminName} فرم‌های حمل و نقل سفارش‌های {$orderIds} را چاپ کرد");

        return view('back.orders.print-all-shipping-forms', compact('orders'));
    }

public function printAllShippingFormsMin(Request $request)
    {
        $this->authorize('orders.view');

        $orders = Order::paid()->whereIn('id', $request->ids)->get();
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';
        $orderIds = implode('، ', $request->ids);

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('export')
            ->withProperties([
                'action' => 'print_shipping_forms_min',
                'order_ids' => $request->ids,
                'orders_count' => $orders->count(),
                'format' => 'shipping_forms_min',
                'ip' => request()->ip()
            ])
            ->log("مدیر {$adminName} فرم‌های خلاصه حمل و نقل سفارش‌های {$orderIds} را چاپ کرد");

        return view('back.orders.print-all-shipping-form-min', compact('orders'));
    }

public function printAll(Request $request)
    {
        $this->authorize('orders.view');

        $orders = Order::paid()->whereIn('id', $request->ids)->get();
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';
        $orderIds = implode('، ', $request->ids);

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('export')
            ->withProperties([
                'action' => 'print_orders',
                'order_ids' => $request->ids,
                'orders_count' => $orders->count(),
                'format' => 'print_all',
                'ip' => request()->ip()
            ])
            ->log("مدیر {$adminName} سفارش‌های {$orderIds} را چاپ کرد");

        return view('back.orders.print-all', compact('orders'));
    }

public function print(Request $request, Order $order)
    {
        $this->authorize('orders.view');
        $id_seller = $request->seller_id;
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        activity()
            ->performedOn($order)
            ->causedBy(auth('adminPanel')->user())
            ->event('export')
            ->withProperties([
                'action' => 'print_order',
                'order_id' => $order->id,
                'seller_id' => $id_seller,
                'ip' => request()->ip()
            ])
            ->log("مدیر {$adminName} سفارش شماره {$order->id} را چاپ کرد");

        return view('back.orders.print', compact('order', 'id_seller'));
    }

public function export(Request $request)
    {
        $this->authorize('orders.export');

        $orders = Order::filter($request)->get();
        $adminName = auth()->user()->full_name ?? auth()->user()->name ?? 'مدیر';
        $ordersCount = $orders->count();
        $format = $request->export_type ?? 'print';

        activity()
            ->causedBy(auth()->user())
            ->event('export')
            ->withProperties([
                'action' => 'export_orders',
                'export_type' => $format,
                'orders_count' => $ordersCount,
                'filters' => $request->except('export_type'),
                'ip' => request()->ip()
            ])
            ->log("مدیر {$adminName} خروجی {$ordersCount} سفارش را با فرمت {$format} دریافت کرد");

        switch ($request->export_type) {
            case 'excel':
                return $this->exportExcel($orders, $request);
                break;
            default:
                return $this->exportPrint($orders, $request);
        }
    }

private function exportExcel($orders)
    {
        return Excel::download(new OrdersExport($orders), 'orders.xlsx');
    }

public function shippingForm(Order $order)
    {
        $this->authorize('orders.view');

        $adminName = auth()->user()->full_name ?? auth()->user()->name ?? 'مدیر';

        activity()
            ->performedOn($order)
            ->causedBy(auth()->user())
            ->event('export')
            ->withProperties([
                'action' => 'view_shipping_form',
                'order_id' => $order->id,
                'ip' => request()->ip()
            ])
            ->log("مدیر {$adminName} فرم حمل و نقل سفارش شماره {$order->id} را مشاهده کرد");

        return view('back.orders.shipping-form', compact('order'));
    }

public function shippingFormMin(Order $order)
    {
        $this->authorize('orders.view');

        $adminName = auth()->user()->full_name ?? auth()->user()->name ?? 'مدیر';

        activity()
            ->performedOn($order)
            ->causedBy(auth()->user())
            ->event('export')
            ->withProperties([
                'action' => 'view_shipping_form_min',
                'order_id' => $order->id,
                'ip' => request()->ip()
            ])
            ->log("مدیر {$adminName} فرم خلاصه حمل و نقل سفارش شماره {$order->id} را مشاهده کرد");

        return view('back.orders.shipping-form-min', compact('order'));
    }
}
