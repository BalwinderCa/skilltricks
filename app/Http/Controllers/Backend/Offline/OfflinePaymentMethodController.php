<?php

namespace App\Http\Controllers\Backend\Offline;

use Illuminate\Http\Request;
use App\Models\SubscriptionHistory;
use App\Http\Controllers\Controller;
use App\Models\OfflinePaymentMethod;
use App\Http\Requests\OfflinePaymentMethodRequestForm;

class OfflinePaymentMethodController extends Controller
{
    # construct
    public function __construct()
    {
        $this->middleware(['permission:offline_payment_methods'])->only('index');
        $this->middleware(['permission:add_offline_payment_methods'])->only(['store']);
        $this->middleware(['permission:edit_offline_payment_methods'])->only(['edit', 'update']);
        $this->middleware(['permission:delete_offline_payment_methods'])->only(['delete']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $offline_payment_methods = OfflinePaymentMethod::oldest()
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            })
            ->paginate(paginationNumber());
   
       
        return view('backend.pages.systemSettings.offlinePaymentMethod.offline_payment', compact('offline_payment_methods'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(OfflinePaymentMethodRequestForm $request)
    {      
        OfflinePaymentMethod::create($this->formatParams($request));
        flash(localize('Offline Payment Method inserted successfully'))->success();
        return redirect()->route('admin.offline-payment.index');
    }
    // format parameters
    protected function formatParams($request, $modelId = null): array
    {
        $params = [
            'name' => $request->name,
            'description' => $request->description

        ];
        if ($modelId) {
            $params['updated_by'] = auth()->user()->id;
        } else {
            $params['created_by'] = auth()->user()->id;
        }
        return $params;
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $method = OfflinePaymentMethod::findOrFail($id);
        return view('backend.pages.systemSettings.offlinePaymentMethod.edit', compact('method'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response
     */
    public function update(OfflinePaymentMethodRequestForm $request)
    {
        OfflinePaymentMethod::where('id', $request->id)->update($this->formatParams($request, $request->id));
        flash(localize('Offline Payment Method Updated successfully'))->success();
        return redirect()->route('admin.offline-payment.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $model = OfflinePaymentMethod::where('id', $id)->first();
        // delete condition 
        // 
        $model->delete();
        flash(localize('Offline Payment Method Deleted successfully'))->success();
        return redirect()->route('admin.offline-payment.index');
    }
}
