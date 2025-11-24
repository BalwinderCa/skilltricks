<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ConstantController extends Controller
{
    //update status

    public function updateStatus(Request $request)
    {

        if (!$request->table) return 0;
        $model = DB::table($request->table)->where('id', $request->id)->first();

        if ($model) {
            DB::table($request->table)->where('id', $request->id)->update(['is_active' => $request->is_active]);
            return 1;
        }
        return 0;
    }
}
