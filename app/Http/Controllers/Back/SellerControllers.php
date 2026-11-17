<?php

namespace App\Http\Controllers\Back;

use App\Exports\OrdersExport;
use App\Exports\SellerOrdersExport;
use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Back\Seller\UpdateSellerRequest;
use App\Models\Carrier;
use App\Models\Category;
use App\Models\NotificationManage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Province;
use App\Models\Seller;
use App\Models\SellerInfo;
use App\Models\SellerVariant;
use App\Models\SizeType;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SellerControllers extends Controller
{
    
public function __construct()
    {
        $this->authorizeResource(Seller::class, 'seller');
        if(option('multi_vendor_system_status','false')=="false"){
            abort(503,'سیستم چند فروشندگی غیرفعال است');
        }
    }

public function sellerOrdersExport(Seller $seller,Request $request)
    {
        $this->authorize('orders.export');
        $orderItem_ids=OrderItem::where('seller_id',$seller->id)->get();
        $order_ids=[];
        foreach ($orderItem_ids as $orderItem_id){
            $order_ids[]=$orderItem_id->order_id;
        }
        $order_ids=array_unique($order_ids);
        $orders = Order::whereIn('id',$order_ids)->filter($request)->get();

        switch ($request->export_type) {
            case 'excel': {
                return $this->exportSellerOrderExcel($orders, $request);
                break;
            }
            default: {
                return $this->exportPrint($orders, $request);
            }
        }
    }

private function exportSellerOrderExcel($orders)
    {
        return Excel::download(new SellerOrdersExport($orders), 'sellersOrders.xlsx');
    }

public function printOrdersSeller(Seller $seller,Order $order)
    {
        $this->authorize('orders.view');
        return view('back.sellers.orders.print', compact('order','seller'));
    }
}
