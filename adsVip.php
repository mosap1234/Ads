<?php
set_time_limit(0);
ini_set('memory_limit', '256M');
error_reporting(0);
ini_set('display_errors', 1);
ignore_user_abort(true);
ini_set('max_execution_time', 300);
date_default_timezone_set('Asia/Baghdad');

// ========== CONFIGURATION ==========
$config = [
    'admin'=> 8419807374, // أيدي الأدمن الأساسي
    'token'=> "8208860189:AAE_L6QJ0F5RQAY9B2d6EHl1v6F0fz_o0As",
    'error_report' => 0,
    'api_url' => 'api.telegram.org',
    'msg_error' => 'Req Failed .',
    'type_up' => 'php://input',
    'user_dev' => "HJ_I_N",
    'name_bot' => 'saleh',
];

error_reporting($config['error_report']);
$API_KEY = $config['token'];
$admin = $config['admin']; // تعريف واحد فقط للأدمن
define('API_KEY', $API_KEY);
define("IDBot", explode(":", $API_KEY)[0]);

// ========== BOT FUNCTIONS ==========
function bot($method, $datas = []) {
    global $config;
    $url = "https://" . $config['api_url'] . "/bot" . API_KEY . "/" . $method;
    $options = [
        'http' => [
            'method'  => 'POST',
            'content' => http_build_query($datas),
            'header'  => 'Content-Type: application/x-www-form-urlencoded\r\n',
        ],
    ];
    $context = stream_context_create($options);
    $res = file_get_contents($url, false, $context);
    
    if ($res === FALSE) {
        return json_encode(['error' => $config['msg_error']]);
    } else {
        return json_decode($res);
    }
}

// ========== INITIALIZATION ==========
$update = json_decode(file_get_contents('php://input'));
if (!$update) {
    echo "No update received";
    exit;
}

// تعريف متغيرات المستخدم
$from_id = null;
$chat_id = null;
$text = null;
$message_id = null;
$data = null;

if ($update->message) {
    $message = $update->message;
    $from_id = $message->from->id;
    $chat_id = $message->chat->id;
    $text = $message->text ?? '';
    $message_id = $message->message_id;
    $name = $message->from->first_name ?? '';
    $username = $message->from->username ?? '';
} elseif ($update->callback_query) {
    $callback = $update->callback_query;
    $from_id = $callback->from->id;
    $chat_id = $callback->message->chat->id;
    $data = $callback->data;
    $message_id = $callback->message->message_id;
    $name = $callback->from->first_name ?? '';
    $username = $callback->from->username ?? '';
}

// ========== SET WEBHOOK (للمرة الأولى فقط) ==========
if (isset($_SERVER['SERVER_NAME']) && isset($_SERVER['SCRIPT_NAME'])) {
    $webhook_url = "https://" . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'];
    echo file_get_contents("https://" . $config['api_url'] . "/bot" . API_KEY . "/setwebhook?url=" . $webhook_url);
}

// ========== SUDO USERS (إضافة المستخدمين الذين يمكنهم الوصول للوحة الإدمن) ==========
$sudo = [$admin]; // يمكنك إضافة أكثر من أدمن هنا
$is_admin = in_array($from_id, $sudo);

// ========== SYSTEM FILES ==========
// تحميل ملفات النظام
$SALEH0 = @file_get_contents("SALEH0.txt") ?: ""; // قناة 1
$SALEH1 = @file_get_contents("SALEH1.txt") ?: ""; // قناة 2

// تحميل بيانات الاعلانات
$json = @json_decode(file_get_contents('ads'), true) ?: [];
$m = @json_decode(file_get_contents('ads_mem'), true) ?: [];

// ========== MANDATORY SUBSCRIPTION CHECK ==========
// هذا الشرط فقط للمستخدمين العاديين، ليس للأدمن
if (!$is_admin && $update->message) {
    $check_subscription = false;
    
    if ($SALEH0) {
        $check1 = @file_get_contents("https://api.telegram.org/bot".API_KEY."/getChatMember?chat_id=$SALEH0&user_id=".$from_id);
        $check_subscription = (strpos($check1, '"status":"left"') !== false || 
                              strpos($check1, '"Bad Request: USER_ID_INVALID"') !== false || 
                              strpos($check1, '"status":"kicked"') !== false);
    }
    
    if ($SALEH1 && !$check_subscription) {
        $check2 = @file_get_contents("https://api.telegram.org/bot".API_KEY."/getChatMember?chat_id=$SALEH1&user_id=".$from_id);
        $check_subscription = (strpos($check2, '"status":"left"') !== false || 
                              strpos($check2, '"Bad Request: USER_ID_INVALID"') !== false || 
                              strpos($check2, '"status":"kicked"') !== false);
    }
    
    if ($check_subscription) {
        // إذا لم يكن مشتركاً، نرسل له رسالة ونوقف التنفيذ لهذا المستخدم فقط
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "🗃 عذراً عزيزي، 🔰\nيجب عليك الإشتراك في قناة المطور أولاً\n\nاشترك ثم ارسل /start 🛍!\n\n@$SALEH0\n@$SALEH1",
        ]);
        exit; // نوقف التنفيذ لهذا المستخدم فقط
    }
}

// ========== ADMIN PANEL ==========
// التحقق أولاً إذا كان المستخدم أدمن ويعمل في لوحة التحكم
$admin_mode_file = "SALEH.txt";
$admin_mode = @file_get_contents($admin_mode_file) ?: "";

// معالجة لوحة الأدمن (الأولوية للأدمن)
if ($is_admin) {
    // ========== ADMIN CALLBACKS ==========
    if ($data) {
        // معالجة جميع callback_data الخاصة باللوحة الإدارية
        switch ($data) {
            case 'back_admin':
            case 'SALEH':
                // عرض لوحة التحكم الرئيسية
                $ch_bot = $json['ch'] ?? 'لايوجد';
                $COUNTADS = count($json['msgs_time'] ?? []);
                
                bot('editmessagetext', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => "👑 *لوحة تحكم الأدمن*\n\n📢 قناة الاعلانات: @$ch_bot\n📊 عدد الإعلانات المحجوزة: $COUNTADS\n⏰ الوقت الحالي: " . date('Y-m-d H:i:s'),
                    'parse_mode' => 'Markdown',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => 'أضافه/خصم رصيد', 'callback_data' => 'add_admin1']],
                            [['text' => 'تعيين قناة النشر', 'callback_data' => 'set_ch']],
                            [['text' => 'تعيين اسم القناة', 'callback_data' => 'SET_NAME'], ['text' => 'تعيين يوزر المطور', 'callback_data' => 'SET_USER']],
                            [['text' => 'قسم الاشتراك الاجباري', 'callback_data' => 'SALEH78']],
                            [['text' => 'قسم الاذاعة', 'callback_data' => '6g77g']],
                            [['text' => 'إحصائيات البوت', 'callback_data' => 'SALEH7']],
                            [['text' => 'رجوع للبوت', 'callback_data' => 'back_bot']],
                        ]
                    ])
                ]);
                @unlink($admin_mode_file);
                break;
                
            case 'SALEH78':
                // قسم الاشتراك الاجباري
                bot('editMessageText', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => "📌 *قسم الاشتراك الاجباري*\n\nاختار القناة التي تريد التحكم بها:",
                    'parse_mode' => 'markdown',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => 'قناة 1', 'callback_data' => 'SALEH765'], ['text' => 'قناة 2', 'callback_data' => 'SALEH907']],
                            [['text' => 'رجوع', 'callback_data' => 'SALEH']],
                        ]
                    ])
                ]);
                break;
                
            case 'SALEH765':
                // التحكم بقناة 1
                bot('editMessageText', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => "⚙️ *التحكم بقناة 1*\n\nالقناة الحالية: @$SALEH0",
                    'parse_mode' => 'markdown',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => 'وضع قناة', 'callback_data' => 'SALEH0'], ['text' => 'حذف قناة', 'callback_data' => 'delete11']],
                            [['text' => 'عرض القناة', 'callback_data' => 'SALEH987']],
                            [['text' => 'رجوع', 'callback_data' => 'SALEH78']],
                        ]
                    ])
                ]);
                break;
                
            case 'SALEH0':
                // طلب إدخال معرف القناة 1
                bot('editMessageText', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => "📝 أرسل معرف قناة 1 (مثال: @channel)\nثم قم برفع البوت أدمن في القناة",
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => 'رجوع', 'callback_data' => 'SALEH765']],
                        ]
                    ])
                ]);
                file_put_contents($admin_mode_file, "set_channel1");
                break;
                
            case 'SALEH987':
                // عرض قناة 1
                bot('editMessageText', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => "📢 *قناة 1 الحالية:*\n@$SALEH0",
                    'parse_mode' => 'markdown',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => 'رجوع', 'callback_data' => 'SALEH765']],
                        ]
                    ])
                ]);
                break;
                
            case 'delete11':
                // تأكيد حذف قناة 1
                bot('editMessageText', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => "⚠️ *تأكيد الحذف*\nهل تريد حذف قناة 1 من الاشتراك الإجباري؟",
                    'parse_mode' => 'markdown',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => '❌ لا', 'callback_data' => 'SALEH765'],
                                ['text' => '✅ نعم', 'callback_data' => 'confirm_delete1']
                            ]
                        ]
                    ])
                ]);
                break;
                
            case 'confirm_delete1':
                // تنفيذ حذف قناة 1
                @unlink("SALEH0.txt");
                bot('editMessageText', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => "✅ تم حذف قناة 1 بنجاح",
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => 'رجوع', 'callback_data' => 'SALEH78']],
                        ]
                    ])
                ]);
                break;
                
            // ... (نفس المنطق للقناة 2 والأقسام الأخرى)
            
            case 'add_admin1':
                // إضافة/خصم رصيد
                bot('editMessageText', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => "💳 *إضافة/خصم رصيد*\n\nأرسل أيدي المستخدم:",
                    'parse_mode' => 'markdown',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => 'رجوع', 'callback_data' => 'SALEH']],
                        ]
                    ])
                ]);
                file_put_contents($admin_mode_file, "add_balance_user");
                break;
                
            case 'SALEH7':
                // إحصائيات البوت
                $members = count(@file("SALEH4.txt") ?: []);
                bot('editMessageText', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => "📊 *إحصائيات البوت*\n\n👥 عدد المشتركين: $members\n⚡ سرعة البوت: 100%\n🤖 اسم البوت: " . ($json['namech'] ?? 'بوت الإعلانات'),
                    'parse_mode' => 'markdown',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => 'رجوع', 'callback_data' => 'SALEH']],
                        ]
                    ])
                ]);
                break;
                
            case '6g77g':
                // قسم الإذاعة
                bot('editMessageText', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => "📢 *قسم الإذاعة*\n\nاختر نوع الإذاعة:",
                    'parse_mode' => 'markdown',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => 'إذاعة توجيه', 'callback_data' => 'SALEH5'], ['text' => 'إذاعة عامة', 'callback_data' => 'SALEH6']],
                            [['text' => 'رجوع', 'callback_data' => 'SALEH']],
                        ]
                    ])
                ]);
                break;
        }
    }
    
    // ========== ADMIN TEXT COMMANDS ==========
    if ($text && $from_id == $admin) {
        // معالجة أوامر النص الخاصة بالأدمن
        switch ($admin_mode) {
            case 'set_channel1':
                if (strpos($text, '@') !== false) {
                    $channel = str_replace('@', '', $text);
                    file_put_contents("SALEH0.txt", $channel);
                    bot('sendMessage', [
                        'chat_id' => $chat_id,
                        'text' => "✅ تم تعيين قناة 1 بنجاح: @$channel",
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [['text' => 'رجوع للوحة', 'callback_data' => 'SALEH78']],
                            ]
                        ])
                    ]);
                    @unlink($admin_mode_file);
                }
                break;
                
            case 'add_balance_user':
                if (is_numeric($text)) {
                    file_put_contents($admin_mode_file, "add_balance_amount:$text");
                    bot('sendMessage', [
                        'chat_id' => $chat_id,
                        'text' => "💰 الآن أرسل المبلغ:\n(استخدم - للخصم مثل: -100)",
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [['text' => 'إلغاء', 'callback_data' => 'SALEH']],
                            ]
                        ])
                    ]);
                }
                break;
        }
        
        // معالجة المبلغ بعد إدخال الأيدي
        if (strpos($admin_mode, 'add_balance_amount:') === 0) {
            $user_id = explode(':', $admin_mode)[1];
            if (is_numeric($text)) {
                $amount = (int)$text;
                $current_balance = $json['point'][$user_id] ?? 0;
                $new_balance = $current_balance + $amount;
                
                $json['point'][$user_id] = $new_balance;
                file_put_contents('ads', json_encode($json));
                
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "✅ تم تعديل رصيد المستخدم $user_id\nالمبلغ: $amount\nالرصيد الجديد: $new_balance"
                ]);
                
                // إعلام المستخدم
                bot('sendMessage', [
                    'chat_id' => $user_id,
                    'text' => "💰 تم تعديل رصيدك بمبلغ: $amount\nرصيدك الحالي: $new_balance"
                ]);
                
                @unlink($admin_mode_file);
            }
        }
    }
    
    // ========== ADMIN START COMMAND ==========
    if ($text == "/start" && $is_admin) {
        $ch_bot = $json['ch'] ?? 'لايوجد';
        $COUNTADS = count($json['msgs_time'] ?? []);
        
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "👑 *مرحباً بك في لوحة التحكم*\n\n📢 قناة الاعلانات: @$ch_bot\n📊 عدد الإعلانات: $COUNTADS\n⏰ الوقت: " . date('Y-m-d H:i:s'),
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => 'أضافه/خصم رصيد', 'callback_data' => 'add_admin1']],
                    [['text' => 'تعيين قناة النشر', 'callback_data' => 'set_ch']],
                    [['text' => 'قسم الاشتراك الاجباري', 'callback_data' => 'SALEH78']],
                    [['text' => 'قسم الاذاعة', 'callback_data' => '6g77g']],
                    [['text' => 'إحصائيات البوت', 'callback_data' => 'SALEH7']],
                    [['text' => 'إعدادات أخرى', 'callback_data' => 'SALEH']],
                ]
            ])
        ]);
        exit; // توقف هنا لأننا لا نريد تنفيذ باقي الكود للأدمن
    }
}

// ========== TERMS ACCEPTANCE SYSTEM ==========
// هذا الجزء للمستخدمين العاديين فقط
if (!$is_admin) {
    if (!isset($m[$from_id]) || $m[$from_id] != true) {
        // المستخدم لم يوافق على الشروط بعد
        if ($text == "/start") {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "📋 *شروط الاستخدام*\n\n1. يمنع تمويل القنوات الإباحية\n2. يمنع تمويل البوتات\n3. يمنع تمويل قنوات الهاك\n4. لا يوجد استرداد للرصيد\n5. يمنع تمويل قنوات البيع والشراء\n\nاضغط على الزر للموافقة:",
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '✅ أوافق على الشروط', 'callback_data' => 'accept_terms']],
                    ]
                ])
            ]);
            exit;
        }
        
        if ($data == 'accept_terms') {
            $m[$from_id] = true;
            file_put_contents('ads_mem', json_encode($m));
            
            bot('editMessageText', [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => "✅ *تم قبول الشروط بنجاح*\n\nيمكنك الآن استخدام البوت",
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '🚀 ابدأ الاستخدام', 'callback_data' => 'start_using']],
                    ]
                ])
            ]);
            exit;
        }
        
        if ($data == 'start_using') {
            // عرض واجهة البوت الرئيسية بعد الموافقة
            $points = $json['point'][$from_id] ?? 0;
            $NAME_C = $json['namech'] ?? $config['name_bot'];
            
            bot('editMessageText', [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => "🎉 *مرحباً بك في بوت $NAME_C*\n\n💰 رصيدك الحالي: $points دينار\n\nاختر الخدمة التي تريدها:",
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => "💰 رصيدك: $points دينار", 'callback_data' => 'balance']],
                        [['text' => '📢 تمويل جديد', 'callback_data' => 'post'], ['text' => '💳 إضافة رصيد', 'callback_data' => 'add_fun']],
                        [['text' => '🔄 تحويل أموال', 'callback_data' => 'transfer']],
                        [['text' => '📅 حجوزاتي', 'callback_data' => 'my_tl'], ['text' => '📋 الأسعار', 'callback_data' => 'prices']],
                    ]
                ])
            ]);
            exit;
        }
        
        // إذا وصلنا هنا، فهذا مستخدم عادي لم يوافق بعد على الشروط
        // ولا نريد تنفيذ أي شيء آخر له
        exit;
    }
}

// ========== MAIN BOT SYSTEM (للمستخدمين العاديين الذين وافقوا على الشروط) ==========
// هذا الجزء يعمل فقط للمستخدمين العاديين الذين وافقوا على الشروط
if (!$is_admin && isset($m[$from_id]) && $m[$from_id] == true) {
    $points = $json['point'][$from_id] ?? 0;
    $NAME_C = $json['namech'] ?? $config['name_bot'];
    
    // معالجة الأوامر الرئيسية
    if ($text == '/start') {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "🎉 *مرحباً بك في بوت $NAME_C*\n\n💰 رصيدك الحالي: $points دينار",
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "💰 رصيدك: $points دينار", 'callback_data' => 'balance']],
                    [['text' => '📢 تمويل جديد', 'callback_data' => 'post'], ['text' => '💳 إضافة رصيد', 'callback_data' => 'add_fun']],
                    [['text' => '🔄 تحويل أموال', 'callback_data' => 'transfer']],
                    [['text' => '📅 حجوزاتي', 'callback_data' => 'my_tl'], ['text' => '📋 الأسعار', 'callback_data' => 'prices']],
                    [['text' => '📢 قناة النشر', 'url' => 'https://t.me/' . ($json['ch'] ?? '')]],
                ]
            ])
        ]);
        exit;
    }
    
    // معالجة callback_data للمستخدمين العاديين
    if ($data) {
        switch ($data) {
            case 'balance':
                bot('editMessageText', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => "💰 *رصيدك الحالي:* $points دينار",
                    'parse_mode' => 'Markdown',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => 'رجوع', 'callback_data' => 'back_bot']],
                        ]
                    ])
                ]);
                break;
                
            case 'back_bot':
                bot('editMessageText', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => "🎉 *مرحباً بك في بوت $NAME_C*\n\n💰 رصيدك الحالي: $points دينار",
                    'parse_mode' => 'Markdown',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => "💰 رصيدك: $points دينار", 'callback_data' => 'balance']],
                            [['text' => '📢 تمويل جديد', 'callback_data' => 'post'], ['text' => '💳 إضافة رصيد', 'callback_data' => 'add_fun']],
                            [['text' => '🔄 تحويل أموال', 'callback_data' => 'transfer']],
                            [['text' => '📅 حجوزاتي', 'callback_data' => 'my_tl'], ['text' => '📋 الأسعار', 'callback_data' => 'prices']],
                            [['text' => '📢 قناة النشر', 'url' => 'https://t.me/' . ($json['ch'] ?? '')]],
                        ]
                    ])
                ]);
                break;
                
            // ... (باقي معالجة callback_data للمستخدمين العاديين)
        }
    }
    
    // معالجة النصوص للمستخدمين العاديين (طلب تمويل، تحويل، إلخ)
    // ... (هنا يبقى كود نظام الاعلانات الأصلي)
}

// ========== AUTO POSTING SYSTEM ==========
// هذا النظام يعمل في الخلفية بغض النظر عن نوع المستخدم
date_default_timezone_set('Asia/Baghdad');
$chat_ads = "@" . ($json['ch'] ?? '');

if ($chat_ads != "@") {
    // نشر الإعلانات المجدولة
    foreach ($json['msgs_time'] ?? [] as $time => $message) {
        if (date('Y-m-d H:i:s') >= $time) {
            bot('sendMessage', [
                'chat_id' => $chat_ads,
                'text' => $message
            ]);
            unset($json['msgs_time'][$time]);
            file_put_contents('ads', json_encode($json));
            break;
        }
    }
    
    // حذف الإعلانات القديمة
    foreach ($json['delete'] ?? [] as $delete_time => $message_id) {
        if (date('Y-m-d H:i:s') >= $delete_time) {
            bot('deleteMessage', [
                'chat_id' => $chat_ads,
                'message_id' => $message_id
            ]);
            unset($json['delete'][$delete_time]);
            file_put_contents('ads', json_encode($json));
            break;
        }
    }
}

echo "OK"; // إرجاع رد للمخدّم
?>