<?php

namespace App\Services\Log;
use Illuminate\Support\Facades\Log;
class LogService
{

    public function commonLog(
        $title = null,
        $payloads = [],
        $channel = "daily"
    )
    {
        $data = [
            "auth_user_id"     => userID(),
            "title"            => $title,
            "request_time"     => miliTimeFormat(),
            "url"              => currentUrl(),
            "payloads"         => json_encode($payloads, JSON_THROW_ON_ERROR),
            "ip"               => $this->getIp(),
            "request_headers"  => request()->headers->all(),
            "userInfo"         => $this->userInfo()
        ];

        Log::channel($channel)->info(json_encode($data));
    }



    public function getIp()
    {

        return request()->ip();
    }

    public function userInfo()
    {

        return request()->getUserInfo();
    }



}
