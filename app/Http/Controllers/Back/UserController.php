<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Imports\UsersImport;
use App\Models\NotificationManage;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Exports\UsersExport;
use App\Models\FieldValue;
use App\Models\Fild;
use App\Models\Role;
use App\Rules\NotSpecialChar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    
public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

public function export(Request $request)
    {
        $this->authorize('users.export');

        $users = User::where('level', '!=', 'creator')
            ->filter($request)
            ->get();

        switch ($request->export_type) {
            case 'excel': {
                    return $this->exportExcel($users, $request);
                    break;
                }
            default: {
                    return $this->exportPrint($users, $request);
                }
        }
    }

private function exportExcel($users, Request $request)
    {
        return Excel::download(new UsersExport($users, $request), 'users.xlsx');
    }

private function exportPrint($users, Request $request)
    {
        
    }
}
