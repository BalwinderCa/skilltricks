<?php

namespace Modules\Support\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Support\Entities\ReplyTicket;
use Modules\Support\Entities\Ticket;
use Modules\Support\Entities\TicketFile;
use Modules\Support\Http\Requests\ReplyTicketRequestForm;

class ReplyController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(int $id)
    {
        $user = auth()->user();
        $ticket = Ticket::where('id', $id)->first();
        return view('support::ticket.reply', compact('ticket'));      
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('support::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(ReplyTicketRequestForm $request)
    {
        $ticket = Ticket::where('id', $request->ticket_id)->first();
        if($ticket->is_active != 1) {
            flash(localize('Ticket Closed'))->warning();
            return redirect()->back();
        }
        $model = ReplyTicket::create($this->formatParams($request));
        $files = $request->file('files');
        if($files) {
            $this->storeImages($files, $model->id, $request->ticket_id);
        }
        
        // notification
        $this->setNotification($request->ticket_id);
        flash(localize('Operation Successfully'))->success();
        return redirect()->back();
    }
    private function formatParams($request, $model = null):array
    {
        $params = [
            'ticket_id'=>$request->ticket_id,
            'replied'=>$request->description
        ];
        if($model){
            $params['updated_by'] = auth()->user()->id;
        }else{
            $params['replied_by'] = auth()->user()->id;
        }

        return $params;
    }
    private function storeImages($image, $modelId, $ticket_id = null)
    {
        $path = 'public/uploads/ticket/reply/';

        $storeImage = new TicketFile();
        $storeImage->ticket_id = $ticket_id;
        $storeImage->replied_id = $modelId;
        $storeImage->file_path = fileUpload($path, $image);
        $storeImage->save();
   
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('support::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('support::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $reply = ReplyTicket::where('id', $id)->when($user->user_type != 'admin', function($q) use($user){
            $q->where('replied_by', $user->id);
        })->first();
        if(!$reply){
            flash(localize('Ticket replied not found for you'))->warning();
            return redirect()->back();
        }
        $files = $reply->replyImages;
        foreach($files as $file) {
            if(file_exists(base_path($file->file_path))){
                unlink(base_path($file->file_path));
            }
        }
        $reply->delete();
        flash(localize('Ticket reply deleted successfully'))->success();
        return redirect()->back();
    }
    private function setNotification($ticket_id, $reply_id = null)
    {
        try {
            if(!$ticket_id) return false;

            $user = auth()->user();
            $ticket = Ticket::where('id', $ticket_id)->first();
            $url = 'dashboard/support/reply/'. $ticket_id;
            $findTicketOwnerType = $ticket->createdBy->user_type;
      
            if ($findTicketOwnerType == 'customer' && isCustomer()) {
                // admin           
                $msg =  localize('Customer Reply ticket');
                saveNotification($msg, $url, 'admin', 1, null, null, null);
       
                // assign category
                $assignStaff = $ticket->category->assignStaff;
                if ($assignStaff) { 
                    saveNotification($msg, $url, $assignStaff->user_type, $assignStaff->id, null, null, null);
                }
            } else if ($user->user_type == 'staffs') {
                    // admin           
                    $msg =  localize('Reply Ticket');
                    saveNotification($msg, $url, 'admin', 1, null, null, null);
    
                    // assign category
                    $assignStaff = $ticket->category->assignStaff;
                    if($assignStaff) { 
                        saveNotification($msg, $url, $findTicketOwnerType, $ticket->created_by, null, null, null);
                    }
            } elseif (isAdmin()) {
                    $msg =  localize('Reply Ticket');                 
                    saveNotification($msg, $url, $findTicketOwnerType, $ticket->created_by, null, null, null);           
            }
        } catch (\Throwable $th) {
            throw $th;
        }
        
      
    }
}
