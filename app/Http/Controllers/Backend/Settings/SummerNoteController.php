<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SummerNoteController extends Controller
{
    //
    public function index()
    {

    }
    public function summernoteFileUpload(Request $request)
    {
        try {
            $path = 'public/uploads/';
            if($request->file){
                $file_name = fileUpload($path, $request->file);
            }
            $file_path = $file_name;
       
            if(file_exists($file_path)) {
                return response()->json(['status'=>true,'file'=>asset($file_path), 'msg'=>'success']);
            }
            return response()->json(['status'=>false, 'file'=>'', 'msg'=>'error']);
            //code...
        } catch (\Throwable $th) {
            return response()->json(['status'=>false, 'file'=>'','msg'=>$th->getMessage()]);
        }
    }
}
