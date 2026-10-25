<?php

namespace App\Http\Controllers;

use App\Services\FacebookReplySender;
use Illuminate\Http\Request;

class FacebookReplyController extends Controller
{
    public function send(Request $request, int $id, FacebookReplySender $sender)
    {
        $data = $request->validate(['reply' => 'required|string|max:2000']);
        $result = $sender->send($request->user()->business_id, $id, $data['reply'], $request->user()->id);

        return redirect()->route('settings.facebook.inbox')->with($result->status === 'sent' ? 'success' : 'error', $result->status === 'sent' ? 'Reply sent to Facebook.' : $result->note);
    }
}
