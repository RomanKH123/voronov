<?php
require __DIR__ . '/../api/telegram.php';
putenv('TELEGRAM_ADMIN_LOGIN=test-admin');
putenv('TELEGRAM_ADMIN_PASSWORD_HASH=' . password_hash('test-password', PASSWORD_DEFAULT));
function check($condition, $label) { if (!$condition) throw new RuntimeException($label); }
$now = 10000; $sequence = 0; $state = ['chats' => [], 'failures' => []];
function message($text, $chat = 1, $type = 'private') {
    global $state, $sequence, $now;
    return telegramHandleUpdate($state, ['update_id' => ++$sequence, 'message' => ['message_id' => $sequence, 'text' => $text, 'chat' => ['id' => $chat, 'type' => $type]]], $now);
}
check(message('/start')['text'] !== '', 'start');
message('test-admin'); $reply = message('test-password');
check($state['chats'][1]['authorized'] === true && $reply['delete_message_id'] !== null, 'valid auth and password deletion');
check(!str_contains(json_encode($state), 'test-password'), 'password not stored');
message('/stop'); check(!$state['chats'][1]['authorized'], 'unsubscribe');
for ($i = 0; $i < 5; $i++) { message('/start'); message('test-admin'); message('wrong'); }
check(count($state['chats'][1]['failures']) === 5, 'five attempts');
message('/start'); message('test-admin'); message('test-password');
check(!$state['chats'][1]['authorized'], 'start does not bypass lockout');
$now += 901; message('/start'); message('test-admin'); message('test-password');
check($state['chats'][1]['authorized'], 'lockout expires');
message('/start', 2); message('wrong-login', 2); message('test-password', 2);
check(!$state['chats'][2]['authorized'], 'wrong login rejected');
message('/start', 2); $now += 301; message('test-admin', 2); message('test-password', 2);
check(!$state['chats'][2]['authorized'], 'expired login rejected');
check(message('/start', 3, 'group') === null, 'groups ignored');
$update = ['update_id' => ++$sequence, 'message' => ['text' => '/start', 'chat' => ['id' => 4, 'type' => 'private']]];
telegramHandleUpdate($state, $update, $now); check(telegramHandleUpdate($state, $update, $now) === null, 'duplicate ignored');
$state['failures'] = array_fill(0, 30, $now); message('/start', 5); message('test-admin', 5); message('test-password', 5);
check(!$state['chats'][5]['authorized'], 'global lockout');
echo "PASS: bot login, logout, lockout, expiry, invalid login, session timeout, groups, duplicates, global limit, no password storage\n";
