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
function banme($update, $MadelineProto, $msg, $send = true)
{
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $msg_id = $update['update']['message']['id'];
        $mods = $responses['banme']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
            );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    if ($msg) {
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
                        }
                        if (isset($userid)) {
                            $banmod = $responses['banme']['banmod'];
                            if (!is_admin_mod(
                                $update,
                                $MadelineProto,
                                $userid,
                                $banmod,
                                true
                            )
                        ) {
                                $username = $id[2];
                                $mention = html_mention($username, $userid);
                                check_json_array('banlist.json', $ch_id);
                                $file = file_get_contents("banlist.json");
                                $banlist = json_decode($file, true);
                                if (array_key_exists($ch_id, $banlist)) {
                                    if (!in_array($userid, $banlist[$ch_id])) {
                                        array_push($banlist[$ch_id], $userid);
                                        file_put_contents(
                                            'banlist.json',
                                            json_encode($banlist)
                                        );
                                        $str = $responses['banme']['banned'];
                                        $repl = array(
                                            "mention" => $mention,
                                            "title" => $title
                                        );
                                        $message = $engine->render($str, $repl);
                                        $default['message'] = $message;
                                        try {
                                            $kick = $MadelineProto->
                                            channels->kickFromChannel(
                                                ['channel' => $peer,
                                                'user_id' => $userid,
                                                'kicked' => true]
                                            );
                                        } catch (
                                            \danog\MadelineProto\RPCErrorException
                                            $e
                                        ) {
                                        }
                                    } else {
                                        $str = $responses['banme']['already'];
                                        $repl = array(
                                            "mention" => $mention
                                        );
                                        $message = $engine->render($str, $repl);
                                        $default['message'] = $message;
                                    }
                                } else {
                                    $banlist[$ch_id] = [];
                                    try {
                                        $kick = $MadelineProto->
                                        channels->kickFromChannel(
                                            ['channel' => $peer,
                                            'user_id' => $userid,
                                            'kicked' => true, ]
                                        );
                                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                                    }
                                    array_push($banlist[$ch_id], $userid);
                                    file_put_contents(
                                        'banlist.json',
                                        json_encode($banlist)
                                    );
                                    $str = $responses['banme']['banned'];
                                    $repl = array(
                                        "mention" => $mention,
                                        "title" => $title
                                    );
                                    $message = $engine->render($str, $repl);
                                    $default['message'] = $message;
                                }
                            }
                        } else {
                            $str = $responses['banme']['idk'];
                            $repl = array("msg" => $msg);
                            $message = $engine->render($str, $repl);
                            $default['message'] = $message;
                        }
                    } else {
                        $message = $responses['banme']['help'];
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message']) && $send) {
            $sentMessage = $MadelineProto->messages->sendMessage($default);
        }
        if (isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
        }
        if (isset($sentMessage) && $send) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}


function unbanme($update, $MadelineProto, $msg)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $mods = $responses['unbanme']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = html_bold($chat['title']);
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
        );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    if ($msg) {
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
                        }
                        if (isset($userid)) {
                            $username = $id[2];
                            $mention = html_mention($username, $userid);
                            check_json_array('banlist.json', $ch_id);
                            $file = file_get_contents("banlist.json");
                            $banlist = json_decode($file, true);
                            if (array_key_exists($ch_id, $banlist)) {
                                if (in_array($userid, $banlist[$ch_id])) {
                                    if (($key = array_search(
                                        $userid,
                                        $banlist[$ch_id]
                                    )) !== false
                                    ) {
                                        unset($banlist[$ch_id][$key]);
                                    }
                                    file_put_contents(
                                        'banlist.json',
                                        json_encode($banlist)
                                    );
                                    $str = $responses['unbanme']['unbanned'];
                                    $repl = array(
                                        "mention" => $mention,
                                        "title" => $title
                                    );
                                    $message = $engine->render($str, $repl);
                                    $default['message'] = $message;
                                    try {
                                        $kick = $MadelineProto->
                                        channels->kickFromChannel(
                                            ['channel' => $peer,
                                            'user_id' => $userid,
                                            'kicked' => false]
                                        );
                                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                                    }
                                } else {
                                    $str = $responses['unbanme']['already'];
                                    $repl = array(
                                        "mention" => $mention
                                    );
                                    $message = $engine->render($str, $repl);
                                    $default['message'] = $message;
                                }
                            } else {
                                $str = $responses['unbanme']['already'];
                                $repl = array(
                                    "mention" => $mention
                                );
                                $message = $engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        } else {
                            $str = $responses['unbanme']['idk'];
                            $repl = array("msg" => $msg);
                            $message = $engine->render($str, $repl);
                            $default['message'] = $message;
                        }
                    } else {
                        $message = $responses['unbanme']['help'];
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}


function kickhim($update, $MadelineProto, $msg)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $mods = $responses['kickhim']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
        );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    if ($msg) {
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
                        }
                        if (isset($userid)) {
                            $kickmod = $responses['kickhim']['kickmod'];
                            if (!is_admin_mod(
                                $update,
                                $MadelineProto,
                                $userid,
                                $kickmod,
                                true
                            )
                            ) {
                                $username = $id[2];
                                $mention = html_mention($username, $userid);
                                try {
                                    $kick = $MadelineProto->
                                    channels->kickFromChannel(
                                        ['channel' => $peer,
                                        'user_id' => $userid,
                                        'kicked' => true]
                                    );
                                    $kickback = $MadelineProto->
                                    channels->kickFromChannel(
                                        ['channel' => $peer,
                                        'user_id' => $userid,
                                        'kicked' => false]
                                    );
                                    $str = $responses['kickhim']['kicked'];
                                    $repl = array(
                                        "mention" => $mention,
                                        "title" => $title
                                    );
                                    $message = $engine->render($str, $repl);
                                    $default['message'] = $message;
                                } catch (\danog\MadelineProto\RPCErrorException $e) {
                                    $message = $responses['kickhim']['already'];
                                    $default['message'] = $message;
                                }

                            }
                        } else {
                            $message = $responses['kickhim']['idk'];
                            $default['message'] = $message;
                        }
                    } else {
                        $message = $responses['kickhim']['help'];
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (!isset($sentMessage)) {
            if (isset($default['message'])) {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
        }
        if (isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
            \danog\MadelineProto\Logger::log($kickback);
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function kickme($update, $MadelineProto)
{
    global $responses, $engine;
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $title = $chat['title'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
        );
        $userid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (!from_admin_mod($update, $MadelineProto)) {
                    $id = catch_id($update, $MadelineProto, $userid);
                    $username = $id[2];
                    $mention = html_mention($username, $userid);
                    try {
                        $kick = $MadelineProto->channels->kickFromChannel(
                            ['channel' => $peer,
                            'user_id' => $userid,
                            'kicked' => true]
                        );
                        $kickback = $MadelineProto->
                        channels->kickFromChannel(
                            ['channel' => $peer,
                            'user_id' => $userid,
                            'kicked' => false]
                        );
                        $str = $responses['kickme']['kicked'];
                        $repl = array(
                            "mention" => $mention,
                            "title" => $title
                        );
                        $message = $engine->render($str, $repl);
                        $default['message'] = $message;
                    } catch (\danog\MadelineProto\RPCErrorException $e) {}
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
            \danog\MadelineProto\Logger::log($kickback);
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function getbanlist($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
        );
        if (is_moderated($ch_id)) {
            check_json_array('banlist.json', $ch_id);
            $file = file_get_contents("banlist.json");
            $banlist = json_decode($file, true);
            if (array_key_exists($ch_id, $banlist)) {
                foreach ($banlist[$ch_id] as $i => $key) {
                    $user = cache_get_info($update, $MadelineProto, (int) $key);
                    $username = catch_id($update, $MadelineProto, $key)[2];
                    $mention = html_mention($username, $key);
                    if (!isset($message)) {
                        $str = $responses['getbanlist']['header'];
                        $repl = array(
                            "title" => $title
                        );
                        $message = $engine->render($str, $repl);
                        $message = $message."$mention - $key\r\n";
                    } else {
                        $message = $message."$mention - $key\r\n";
                    }
                }
            }
            if (!isset($message)) {
                $str = $responses['getbanlist']['none'];
                $repl = array(
                    "title" => $title
                );
                $message = $engine->render($str, $repl);
                $default['message'] = $message;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (!isset($sentMessage)) {
                $default['message'] = $message;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (isset($sentMessage)) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        }
    }
}

function unbanall($update, $MadelineProto, $msg)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
        );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_master($update, $MadelineProto)) {
                    if ($msg) {
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
                        }
                        if (isset($userid)) {
                            $username = $id[2];
                            $mention = html_mention($username, $userid);
                            check_json_array('gbanlist.json', false, false);
                            $file = file_get_contents("gbanlist.json");
                            $gbanlist = json_decode($file, true);
                            if (in_array($userid, $gbanlist)) {
                                if (($key = array_search(
                                    $userid,
                                    $gbanlist
                                )) !== false
                                ) {
                                    unset($gbanlist[$key]);
                                }
                                file_put_contents(
                                    'gbanlist.json',
                                    json_encode($gbanlist)
                                );
                                $str = $responses['unbanall']['unbanned'];
                                $repl = array(
                                    "mention" => $mention
                                );
                                $message = $engine->render($str, $repl);
                                $default['message'] = $message;
                                try {
                                    $kick = $MadelineProto->
                                    channels->kickFromChannel(
                                        ['channel' => $peer,
                                        'user_id' => $userid,
                                        'kicked' => false]
                                    );
                                } catch (\danog\MadelineProto\RPCErrorException $e) {}
                            } else {
                                $str = $responses['unbanall']['already'];
                                $repl = array(
                                    "mention" => $mention
                                );
                                $message = $engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        } else {
                            $str = $responses['unbanall']['idk'];
                            $repl = array(
                                "msg" => $msg
                            );
                            $message = $engine->render($str, $repl);
                            $default['message'] = $message;
                        }
                    } else {
                        $message = $responses['unbanall']['help'];
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function banall($update, $MadelineProto, $msg, $send = true)
{
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_master($update, $MadelineProto)) {
                    if ($msg) {
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
                        }
                        if (isset($userid)) {
                            $banmod = $responses['banall']['banmod'];
                            if (!is_admin_mod(
                                $update,
                                $MadelineProto,
                                $userid,
                                $banmod,
                                true
                            )
                            ) {
                                if (isset($userid)) {
                                    $username = $id[2];
                                    $mention = html_mention($username, $userid);
                                    check_json_array('gbanlist.json', false, false);
                                    $file = file_get_contents("gbanlist.json");
                                    $gbanlist = json_decode($file, true);
                                    if (!in_array($userid, $gbanlist)) {
                                        array_push($gbanlist, $userid);
                                        file_put_contents(
                                            'gbanlist.json',
                                            json_encode($gbanlist)
                                        );
                                        $str = $responses['banall']['banned'];
                                        $repl = array(
                                            "mention" => $mention
                                        );
                                        $message = $engine->render($str, $repl);
                                        $default['message'] = $message;
                                        try {
                                            $kick = $MadelineProto->
                                            channels->kickFromChannel(
                                                ['channel' => $peer,
                                                'user_id' => $userid,
                                                'kicked' => true]
                                            );
                                        } catch (
                                        \danog\MadelineProto\RPCErrorException
                                        $e
                                        ) {}
                                    } else {
                                        $str = $responses['banall']['already'];
                                        $repl = array(
                                            "mention" => $mention
                                        );
                                        $message = $engine->render($str, $repl);
                                        $default['message'] = $message;
                                    }
                                } else {
                                    $str = $responses['banall']['idk'];
                                    $repl = array(
                                        "msg" => $msg
                                    );
                                    $message = $engine->render($str, $repl);
                                    $default['message'] = $message;
                                }
                            }
                        } else {
                            $str = $responses['banall']['idk'];
                            $repl = array(
                                "msg" => $msg
                            );
                            $message = $engine->render($str, $repl);
                            $default['message'] = $message;
                        }
                    } else {
                        $message = $responses['banall']['help'];
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message']) && $send) {
            $sentMessage = $MadelineProto->messages->sendMessage($default);
        }
        if (isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
        }
        if (isset($sentMessage) && $send) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function getgbanlist($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
        );
        if (is_moderated($ch_id)) {
            check_json_array('gbanlist.json', false, false);
            $file = file_get_contents("gbanlist.json");
            $gbanlist = json_decode($file, true);
            foreach ($gbanlist as $i => $key) {
                $username = catch_id($update, $MadelineProto, $key)[2];
                $mention = html_mention($username, $key);
                if (!isset($message)) {
                    $str = $responses['getgbanlist']['header'];
                    $repl = array(
                        "title" => $title
                    );
                    $message = $engine->render($str, $repl);
                    $message = $message."$mention - $key\r\n";
                } else {
                    $message = $message."$mention - $key\r\n";
                }
            }
            if (!isset($message)) {
                $message = $responses['getgbanlist']['none'];
                $default['message'] = $message;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (!isset($sentMessage)) {
                $default['message'] = $message;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (isset($sentMessage)) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        }
    }
}