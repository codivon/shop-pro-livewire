<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\Province\StoreProvinceRequest;
use App\Http\Requests\Back\Province\UpdateProvinceRequest;
use App\Models\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    
public function __construct()
    {
        $this->authorizeResource(Province::class, 'province');
    }

public function getCities(Request $request)
    {
        $province = Province::findOrFail($request->id);

        return response()->json($province->cities()->orderBy('ordering')->active()->get(['id', 'name']));
    }
}
