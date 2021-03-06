<?php
/**
    along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */
function from_master($update, $MadelineProto, $str = '', $send = false)
{
    $user = cache_from_user_info($update, $MadelineProto);
    $master = cache_get_info($update, $MadelineProto, getenv('MASTER_USERNAME'));
    if ($user['bot_api_id'] == $master['bot_api_id']
        or in_array($user['bot_api_id'], json_decode(getenv('SUDO'), true))
        or $user['bot_api_id'] == $MadelineProto->API->bot_api_id
        or $user['bot_api_id'] == $MadelineProto->API->bot_id
    ) {
        return true;
    } else {
        if ($send) {
            $peer = $MadelineProto->get_info($update['update']['message']['to_id'])['InputPeer'];
            $msg_id = $update['update']['message']['id'];
            $message = $str;
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' => $msg_id, 'message' => $message]
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        }

        return false;
    }
}

function is_master($MadelineProto, $userid)
{
    if ($userid == $MadelineProto->get_info(
        getenv('MASTER_USERNAME')
    )['bot_api_id']
        or $userid == $MadelineProto->get_info(
        getenv('BOT_API_USERNAME')
    )['bot_api_id']
     ) {
        return true;
    } else {
        return false;
    }
}

function from_admin($update, $MadelineProto, $str = '', $send = false)
{
    if (bot_present($update, $MadelineProto, true)) {
        $ch_id = -100 .$update['update']['message']['to_id']['channel_id'];
        $userid = $MadelineProto->get_info(
            $update['update']['message']['from_id']
        )['bot_api_id'];
        if (!isset($MadelineProto->API->cache[$ch_id])) {
            $MadelineProto->API->cache[$ch_id] = [];
        }
        if (!isset($MadelineProto->API->cache[$ch_id]['admins'])) {
            $MadelineProto->API->cache[$ch_id]['admins'] = [];
        }
        if (isset($MadelineProto->API->cache[$ch_id]['admins'][$userid])) {
            $diff = time() - $MadelineProto->API->cache[$ch_id]['admins'][$userid]['timestamp'];
            if ($diff < 300) {
                return $MadelineProto->API->cache[$ch_id]['admins'][$userid]['return'];
            }
        }
        $admins = cache_get_chat_info($update, $MadelineProto);
        foreach ($admins['participants'] as $key) {
            if (array_key_exists('user', $key)) {
                $id = $key['user']['id'];
            } else {
                if (array_key_exists('bot', $key)) {
                    $id = $key['bot']['id'];
                }
            }
            if ($id == $userid) {
                if (array_key_exists('role', $key)) {
                    if ($key['role'] == 'moderator'
                        or $key['role'] == 'creator'
                        or $key['role'] == 'editor'
                    ) {
                        $mod = true;
                        break;
                    } else {
                        $mod = false;
                        break;
                    }
                } else {
                    $mod = false;
                    break;
                }
            }
            $mod = false;
        }
        if ($mod or from_master($update, $MadelineProto)) {
            $MadelineProto->API->cache[$ch_id]['admins'][$userid] = ['timestamp' => time(), 'return' => true];

            return true;
        } else {
            if ($send) {
                $peer = $MadelineProto->get_info($update['update']['message']['to_id'])['InputPeer'];
                $msg_id = $update['update']['message']['id'];
                $message = $str;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    ['peer' => $peer, 'reply_to_msg_id' => $msg_id, 'message' => $message]
                );
                \danog\MadelineProto\Logger::log($sentMessage);
            }
            $MadelineProto->API->cache[$ch_id]['admins'][$userid] = ['timestamp' => time(), 'return' => false];

            return false;
        }
    } else {
        $MadelineProto->API->cache[$ch_id]['admins'][$userid] = ['timestamp' => time(), 'return' => false];

        return false;
    }
}

function is_admin($update, $MadelineProto, $userid, $send = false, $ch_id = false)
{
    try {
        if (bot_present($update, $MadelineProto, true, $ch_id)) {
            if (!$ch_id) {
                $admins = cache_get_chat_info($update, $MadelineProto);
            } else {
                $admins = cache_get_info($update, $MadelineProto, $ch_id, true);
            }
            $ch_id = $admins['id'];
            if (!isset($MadelineProto->API->cache[$ch_id])) {
                $MadelineProto->API->cache[$ch_id] = [];
            }
            if (!isset($MadelineProto->API->cache[$ch_id]['admins'])) {
                $MadelineProto->API->cache[$ch_id]['admins'] = [];
            }
            if (isset($MadelineProto->API->cache[$ch_id]['admins'][$userid])) {
                $diff = time() - $MadelineProto->API->cache[$ch_id]['admins'][$userid]['timestamp'];
                if ($diff < 300) {
                    return $MadelineProto->API->cache[$ch_id]['admins'][$userid]['return'];
                }
            }
            foreach ($admins['participants'] as $key) {
                if (array_key_exists('user', $key)) {
                    $id = $key['user']['id'];
                } else {
                    if (array_key_exists('bot', $key)) {
                        $id = $key['bot']['id'];
                    }
                }
                if ($id == $userid) {
                    if (array_key_exists('role', $key)) {
                        if ($key['role'] == 'moderator'
                            or $key['role'] == 'creator'
                            or $key['role'] == 'editor'
                        ) {
                            $mod = true;
                            break;
                        } else {
                            $mod = false;
                            break;
                        }
                    } else {
                        $mod = false;
                        break;
                    }
                }
                $mod = false;
            }
            if ($mod or is_master($MadelineProto, $userid)) {
                $MadelineProto->API->cache[$ch_id]['admins'][$userid] = ['timestamp' => time(), 'return' => true];

                return true;
            } else {
                if ($send) {
                    $peer = $MadelineProto->get_info($update['update']['message']['to_id'])['InputPeer'];
                    $msg_id = $update['update']['message']['id'];
                    $message = $str;
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        ['peer' => $peer, 'reply_to_msg_id' => $msg_id, 'message' => $message]
                    );
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
                $MadelineProto->API->cache[$ch_id]['admins'][$userid] = ['timestamp' => time(), 'return' => false];

                return false;
            }
        } else {
            $MadelineProto->API->cache[$ch_id]['admins'][$userid] = ['timestamp' => time(), 'return' => false];

            return false;
        }
    } catch (Exception $e) {
        $MadelineProto->API->cache[$ch_id]['admins'][$userid] = ['timestamp' => time(), 'return' => false];

        return false;
    }
}

function is_bot_admin($update, $MadelineProto, $send = false)
{
    try {
        $chat = cache_get_chat_info($update, $MadelineProto);
        $peer = $chat['id'];
        $bot_id = $MadelineProto->API->bot_id;
        $bot_api_id = $MadelineProto->API->bot_api_id;
        $mod = is_admin($update, $MadelineProto, $bot_id);
        if ($mod) {
            return true;
        } else {
            if ($send) {
                $msg_id = $update['update']['message']['id'];
                $message = 'My helper bot has to be an admin for me to work properly. Message me /start for more info';
                $sentMessage = $MadelineProto->messages->sendMessage(
                    ['peer' => $peer, 'reply_to_msg_id' => $msg_id, 'message' => $message]
                );
                \danog\MadelineProto\Logger::log($sentMessage);
            }

            return false;
        }
    } catch (Exception $e) {
        return false;
    }
}

function from_mod($update, $MadelineProto)
{
    if (bot_present($update, $MadelineProto, true)) {
        $ch_id = -100 .$update['update']['message']['to_id']['channel_id'];
        $userid = $MadelineProto->get_info(
            $update['update']['message']['from_id']
        )['bot_api_id'];
        if (!isset($MadelineProto->API->cache[$ch_id])) {
            $MadelineProto->API->cache[$ch_id] = [];
        }
        if (!isset($MadelineProto->API->cache[$ch_id]['mods'])) {
            $MadelineProto->API->cache[$ch_id]['mods'] = [];
        }
        if (isset($MadelineProto->API->cache[$ch_id]['mods'][$userid])) {
            $diff = time() - $MadelineProto->API->cache[$ch_id]['mods'][$userid]['timestamp'];
            if ($diff < 300) {
                return $MadelineProto->API->cache[$ch_id]['mods'][$userid]['return'];
            }
        }
        if (!file_exists('promoted.json')) {
            $json_data = [];
            $json_data[$ch_id] = [];
            file_put_contents('promoted.json', json_encode($json_data));
        }
        $file = file_get_contents('promoted.json');
        $promoted = json_decode($file, true);
        if (isset($promoted[$ch_id])) {
            if (in_array($userid, $promoted[$ch_id])) {
                $mod = true;
            } else {
                $mod = false;
            }
            if ($mod or from_master($update, $MadelineProto)) {
                $MadelineProto->API->cache[$ch_id]['mods'][$userid] = ['timestamp' => time(), 'return' => true];

                return true;
            } else {
                $MadelineProto->API->cache[$ch_id]['mods'][$userid] = ['timestamp' => time(), 'return' => false];

                return false;
            }
        } else {
            $MadelineProto->API->cache[$ch_id]['mods'][$userid] = ['timestamp' => time(), 'return' => false];

            return false;
        }
    } else {
        $MadelineProto->API->cache[$ch_id]['mods'][$userid] = ['timestamp' => time(), 'return' => false];

        return false;
    }
}

function is_mod($update, $MadelineProto, $userid, $ch_id = false)
{
    if (bot_present($update, $MadelineProto, true, $ch_id)) {
        if (!$ch_id) {
            $ch_id = -100 .$update['update']['message']['to_id']['channel_id'];
        }
        if (!isset($MadelineProto->API->cache[$ch_id])) {
            $MadelineProto->API->cache[$ch_id] = [];
        }
        if (!isset($MadelineProto->API->cache[$ch_id]['mods'])) {
            $MadelineProto->API->cache[$ch_id]['mods'] = [];
        }
        if (isset($MadelineProto->API->cache[$ch_id]['mods'][$userid])) {
            $diff = time() - $MadelineProto->API->cache[$ch_id]['mods'][$userid]['timestamp'];
            if ($diff < 300) {
                return $MadelineProto->API->cache[$ch_id]['mods'][$userid]['return'];
            }
        }
        if (!file_exists('promoted.json')) {
            $json_data = [];
            $json_data[$ch_id] = [];
            file_put_contents('promoted.json', json_encode($json_data));
        }
        $file = file_get_contents('promoted.json');
        $promoted = json_decode($file, true);
        if (isset($promoted[$ch_id])) {
            if (in_array($userid, $promoted[$ch_id])) {
                $mod = true;
            } else {
                $mod = false;
            }
            if ($mod or is_master($MadelineProto, $userid)) {
                $MadelineProto->API->cache[$ch_id]['mods'][$userid] = ['timestamp' => time(), 'return' => true];

                return true;
            } else {
                $MadelineProto->API->cache[$ch_id]['mods'][$userid] = ['timestamp' => time(), 'return' => false];

                return false;
            }
        } else {
            $MadelineProto->API->cache[$ch_id]['mods'][$userid] = ['timestamp' => time(), 'return' => false];

            return false;
        }
    } else {
        $MadelineProto->API->cache[$ch_id]['mods'][$userid] = ['timestamp' => time(), 'return' => false];

        return false;
    }
}

function from_admin_mod($update, $MadelineProto, $str = '', $send = false)
{
    if (from_mod($update, $MadelineProto)
        or from_admin($update, $MadelineProto)
        or from_master($update, $MadelineProto)
    ) {
        return true;
    } else {
        if ($send) {
            $peer = $MadelineProto->get_info($update['update']['message']['to_id'])['InputPeer'];
            $msg_id = $update['update']['message']['id'];
            $message = $str;
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' => $msg_id, 'message' => $message]
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        }

        return false;
    }
}

function is_admin_mod($update, $MadelineProto, $userid, $str = '', $send = false, $ch_id = false)
{
    if (!$ch_id) {
        $ismod = is_mod($update, $MadelineProto, $userid);
        $isadmin = is_admin($update, $MadelineProto, $userid);
    } else {
        $ismod = is_mod($update, $MadelineProto, $userid, $ch_id);
        $isadmin = is_admin($update, $MadelineProto, $userid, false, $ch_id);
    }
    if ($ismod
        or $isadmin
        or is_master($MadelineProto, $userid)
    ) {
        if ($send) {
            $peer = $MadelineProto->get_info($update['update']['message']['to_id'])['InputPeer'];
            $msg_id = $update['update']['message']['id'];
            $message = $str;
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' => $msg_id, 'message' => $message]
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        }

        return true;
    } else {
        return false;
    }
}

function is_chat_owner($update, $MadelineProto, $ch_id, $userid)
{
    if (is_master($MadelineProto, $userid)) {
        return true;
    }
    try {
        $admins = cache_get_info($update, $MadelineProto, $ch_id, true);
        $peer = $admins['id'];
        foreach ($admins['participants'] as $key) {
            if (array_key_exists('user', $key)) {
                $id = $key['user']['id'];
            } else {
                if (array_key_exists('bot', $key)) {
                    $id = $key['bot']['id'];
                }
            }
            if ($id == $userid) {
                if (array_key_exists('role', $key)) {
                    if ($key['role'] == 'creator'
                    ) {
                        $mod = true;
                        break;
                    } else {
                        $mod = false;
                        break;
                    }
                } else {
                    $mod = false;
                    break;
                }
            }
            $mod = false;
        }
        if ($mod) {
            return true;
        } else {
            return false;
        }
    } catch (Exception $e) {
        return false;
    }
}

function are_mods_restricted($ch_id)
{
    check_json_array('settings.json', $ch_id);
    $file = file_get_contents('settings.json');
    $settings = json_decode($file, true);
    if (!isset($settings[$ch_id]['restrict_mods'])) {
        return false;
    }
    if (!$settings[$ch_id]['restrict_mods']) {
        return false;
    }

    return true;
}

function alert_check($ch_id, $userid)
{
    check_json_array('settings.json', $ch_id);
    $file = file_get_contents('settings.json');
    $settings = json_decode($file, true);
    if (!isset($settings[$ch_id][$userid])) {
        $settings[$ch_id][$userid] = [];
    }
    if (!isset($settings[$ch_id][$userid]['alertme'])) {
        $settings[$ch_id][$userid]['alertme'] = false;
    }
    if (!$settings[$ch_id][$userid]['alertme']) {
        return false;
    } else {
        return true;
    }
}
