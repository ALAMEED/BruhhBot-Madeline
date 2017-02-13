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
function addadmin($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Only my master can promote new admins";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_master($update, $MadelineProto, $mods, true)) {
                    $id = catch_id($update, $MadelineProto, $msg);
                    if ($id[0]) {
                        $userid = $id[1];
                        $username = $id[2];
                    } else {
                        $message = "I can't find a user called ".
                        "$msg. Who's that?";
                        $default['message'] = $message;
                    }
                    if (isset($userid)) {
                            $channelRoleModerator = [
                                '_' => 'channelRoleModerator',
                            ];
                            try {
                                $editadmin = $MadelineProto->channels->editAdmin(
                                    ['channel' => $peer, 'user_id' => $userid,
                                    'role' => $channelRoleModerator ]
                                );
                                $entity = create_mention(0, $username, $userid);
                                $message = "$username is now an admin of $title";
                                $len = strlen($message) - strlen($title);
                                $entity[] = create_style('bold', $len, $title, false);
                                $default['message'] = $message;
                                $default['entities'] = $entity;
                                \danog\MadelineProto\Logger::log($editadmin);

                            } catch (Exception $e) {
                                $message = "I am not the owner of this chat, and ".
                                "cannot add any admins";
                                $default['message'] = $message;
                            }
                    }
                    if (isset($default['message'])) {
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
    }
}
function rmadmin($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Only my master can humiliate someone like this";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_master($update, $MadelineProto, $mods, true)) {
                    $id = catch_id($update, $MadelineProto, $msg);
                    if ($id[0]) {
                        $userid = $id[1];
                        $username = $id[2];
                    } else {
                        $message = "I can't find a user called ".
                        "$msg. Who's that?";
                        $default['message'] = $message;
                    }
                    if (isset($userid)) {
                        try {
                            $channelRoleEmpty = ['_' => 'channelRoleEmpty', ];
                            $editadmin = $MadelineProto->channels->editAdmin(
                                ['channel' => $peer, 'user_id' => $userid,
                                'role' => $channelRoleEmpty ]
                            );
                            \danog\MadelineProto\Logger::log($editadmin);
                            $entity = create_mention(0, $username, $userid);
                            $message = "$username is no longer an admin of $title.".
                            " I am sorry";
                            $len = strlen($message) - strlen($title) - 12;
                            $entity[] = create_style('bold', $len, $title, false);
                            $default['message'] = $message;
                            $default['entities'] = $entity;
                        } catch (Exception $e) {
                            $message = "I am not the owner of this group, and cannot ".
                            "add or remove admins.";
                            $default['message'] = $message;
                        }
                    }
                    if (isset($default['message'])) {
                        $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                    }
                }
                if (isset($sentMessage)) {
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
    }
}
function idme($update, $MadelineProto, $msg)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        $noid = "Your Telegram ID is $peer";
        $cont = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $noid = "The Telegram ID of $title is $ch_id";
        $cont = true;
    }
    $msg_id = $update['update']['message']['id'];
    $default = array(
        'peer' => $peer,
        'reply_to_msg_id' => $msg_id,
        );
    if (isset($cont)) {
        if (!empty($msg)) {
            $id = catch_id($update, $MadelineProto, $msg);
            if ($id[0]) {
                $username = $id[2];
                $userid = $id[1];
                $mention = create_mention(19, $username, $userid);
                $message = "The Telegram ID of $username is $userid";
                $default['message'] = $message;
            }
            if (!isset($message)) {
                $message = "I can't find a user called $msg. Who's that?";
                $default['message'] = $message;
            }
        } else {
            $message = $noid;
            $default['message'] = $message;
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function adminlist($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $message = "Admins for $title:\r\n";
        $style = create_style('bold', 0, $message, false);
        $admins = cache_get_chat_info($update, $MadelineProto);
        foreach ($admins['participants'] as $key) {
            if (array_key_exists('user', $key)) {
                $id = $key['user']['id'];
            } else {
                if (array_key_exists('bot', $key)) {
                    $id = $key['bot']['id'];
                }
            }
            $username = catch_id($update, $MadelineProto, $id)[2];
            if (array_key_exists("role", $key)) {
                if ($key['role'] == "moderator"
                    or $key['role'] == "creator") {
                    $mod = true;
                } else {
                    $mod = false;
                }
            } else {
                $mod = false;
            }
            if ($mod) {
                if (!isset($entity)) {
                    $offset = strlen($message);
                    $entity = create_mention($offset, $username, $id);
                    $length = $offset + strlen($username) + strlen($id) + 5;
                    $message = $message."$username [$id]\r\n";
                } else {
                    $entity[] = create_mention($length, $username, $id, false);
                    $length = $length + strlen($username) + strlen($id) + 5;
                    $message = $message."$username [$id]\r\n";
                }
            }
        }
        $entity[] = $style;
        $default['message'] = $message;
        $default['entities'] = $entity;
        $sentMessage = $MadelineProto->messages->sendMessage(
            $default
        );
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function modlist($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        $mods = "Only mods can use me to kick butts!";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        if (is_moderated($ch_id)) {
            $message = "Moderators for $title:\r\n";
            $style = create_style('bold', 0, $message, false);
            check_json_array('promoted.json', $ch_id);
            $file = file_get_contents("promoted.json");
            $promoted = json_decode($file, true);
            if (array_key_exists($ch_id, $promoted)) {
                foreach ($promoted[$ch_id] as $i => $key) {
                    $username = catch_id($update, $MadelineProto, $key)[2];
                    if (!isset($entity)) {
                        $offset = strlen($message);
                        $entity = create_mention($offset, $username, $key);
                        $length = $offset + strlen($username) + strlen($key) + 5;
                        $message = $message."$username [$key]\r\n";
                    } else {
                        $entity[] = create_mention($length, $username, $key, false);
                        $length = $length + strlen($username) + strlen($key) + 5;
                        $message = $message."$username [$key]\r\n";
                    }
                }
                $default['message'] = $message;
                $default['entities'] = $entity;
            }
            if (!isset($entity)) {
                $entity = create_style('bold', 28, $title);
                $message = "There are no moderators for ".$title;
                $default['message'] = $message;
                $default['entities'] = $entity;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function pinmessage($update, $MadelineProto, $silent)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        $mods = "I can pin messages! But YOU can't make me!!! ;)";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto, true)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    if (array_key_exists(
                        "reply_to_msg_id",
                        $update['update']['message']
                    )
                    ) {
                        try {
                        $pin_id = $update['update']['message']['reply_to_msg_id'];
                        $pin = $MadelineProto->
                        channels->updatePinnedMessage(
                            ['silent' => $silent,
                            'channel' => $peer,
                            'id' => $pin_id ]
                        );
                        $message = "Message successfully pinned!";
                        $default['message'] = $message;
                        \danog\MadelineProto\Logger::log($pin);
                        } catch (Exception $e) {
                        }
                    } else {
                        $entity = create_style('code', 37, 6);
                        $entity[] = create_style('code', 75, 12, false);
                        $message = "Pin a message by replying to it with \r\n/pin\r\n".
                        "to pin it silently, reply with /pin silent";
                        $default['message'] = $message;
                        $default['entities'] = $entity;
                    }
                }
                if (isset($default['message'])) {
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
    }
}

function delmessage($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto, true)) {
                if (from_admin_mod($update, $MadelineProto)) {
                    if (array_key_exists(
                        "reply_to_msg_id",
                        $update['update']['message']
                    )
                    ) {
                        try {
                        $del_id = $update['update']['message']['reply_to_msg_id'];
                        $delete = $MadelineProto->channels->deleteMessages(
                            ['channel' => $peer,
                            'id' => [$del_id]]
                        );
                        \danog\MadelineProto\Logger::log($delete);
                        $del_id = $msg_id - 1;
                        $delete = $MadelineProto->channels->deleteMessages(
                            ['channel' => $peer,
                            'id' => [$msg_id]]
                        );
                        \danog\MadelineProto\Logger::log($delete);
                        } catch (Exception $e) {
                        }
                    } else {
                        $entity = create_style('code', 40, 6);
                        $message = "Delete a message by replying to it with \r\n/del";
                        $default['message'] = $message;
                        $default['entities'] = $entity;
                    }
                }
                if (isset($default['message'])) {
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
    }
}

function delmessage_user($update, $MadelineProto, $msg)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto, true)) {
                if (from_admin_mod($update, $MadelineProto)) {
                    if ($msg) {
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
                            $username = $id[2];
                        } else {
                            $message = "I can't find a user called ".
                            "$msg. Who's that?";
                            $default['message'] = $message;
                        }
                        if (isset($userid)) {
                            if (!is_admin_mod($update, $MadelineProto, $userid)) {
                                try {
                                    $delete = $MadelineProto->channels->deleteUserHistory(
                                        ['channel' => $peer,
                                        'user_id' => $userid]
                                    );
                                    \danog\MadelineProto\Logger::log($delete);
                                    $message = "The message history of $username ".
                                    "has been wiped from this chat";
                                    $mention = create_mention(23, $username, $userid);
                                    $default['message'] = $message;
                                    $default['entities'] = $mention;
                                } catch (Exception $e) {}
                            } else {
                                $message = "You can't erase the msg history of a moderator";
                                $default['message'] = $message;
                            }
                        }
                    } else {
                        $entity = create_style('code', 4, 14);
                        $message = "Use /del @username to delete the message ".
                        "history of a user";
                        $default['message'] = $message;
                        $default['entities'] = $entity;
                    }
                }
                if (isset($default['message'])) {
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
    }
}
