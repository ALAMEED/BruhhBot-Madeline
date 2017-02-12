<?php
/**
    Copyright (C) 2016-2017 Hunter Ashton

    This file is part of BruhhBot.

    BruhhBot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    BruhhBot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */
/* server timezone */
define('CONST_SERVER_TIMEZONE', 'UTC');

/* server dateformat */
define('CONST_SERVER_DATEFORMAT', 'l, j - H:i:s');

function getloc($update, $MadelineProto, $area)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
    }
    if ($cont) {
        $msg_id = $update['update']['message']['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $response = Requests::get(
            "https://maps.googleapis.com/maps/api/geocode/json?address="
            . str_replace(" ", "%20", $area)
        );
        $status = $response->status_code;
        // var_dump($status);
        $headers = array('Accept' => 'application/json');
        $responsej = json_decode($response->body, true);
        // var_dump(json_decode($response->body, true));
        if ($responsej['status'] == 'OK') {
            // var_dump($responsej['results'][0]['geometry']['location']);
            $lat = $responsej['results'][0]['geometry']['location']['lat'];
            $lng = $responsej['results'][0]['geometry']['location']['lng'];
            $timestamp = time();
            $api_response = Requests::get(
                "https://maps.googleapis.com/maps/api/timezone/json?location=".
                "$lat,$lng&timestamp=$timestamp"
            );
            $api_responsej = json_decode($api_response->body, true);
            $ctime = now($api_responsej['timeZoneId']);
            $timezone = $api_responsej['timeZoneId'];
            $return = "The current time in $timezone is $ctime";
            $message = str_replace("_", " ", $return);
            $default['message'] = $message;
        } else {
            $message = 'What the actual hell is "' . $area . '"';
            $default['message'] = $message;
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}
function now($str_user_timezone,
    $str_server_timezone = CONST_SERVER_TIMEZONE,
    $str_server_dateformat = CONST_SERVER_DATEFORMAT
) {

    // set timezone to user timezone
    date_default_timezone_set($str_user_timezone);

    $date = new DateTime('now');
    $date->setTimezone(new DateTimeZone($str_user_timezone));
    $str_server_now = $date->format($str_server_dateformat);

    // return timezone to server default
    date_default_timezone_set($str_server_timezone);

    return $str_server_now;
}
