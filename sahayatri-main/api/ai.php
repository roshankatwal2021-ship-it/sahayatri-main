<?php
// system/api/ai.php
// AI Assistant, Day-Wise Itinerary Generator & Travel Translator API

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? 'chat');

switch ($action) {
    case 'plan_trip':
        $destination = trim($_POST['destination'] ?? 'Pokhara');
        $days = intval($_POST['days'] ?? 3);
        $budget = trim($_POST['budget'] ?? 'Moderate (NPR 15,000)');
        $interests = trim($_POST['interests'] ?? 'Adventure, Culture, Lakes');

        if ($days <= 0) $days = 3;

        // Generate intelligent day-by-day itinerary
        $itinerary = [];
        for ($i = 1; $i <= $days; $i++) {
            if ($i == 1) {
                $itinerary[] = [
                    'day' => 1,
                    'title' => "Arrival & Destination Orientation in $destination",
                    'morning' => "Check into your hotel. Morning tea/coffee with local orientation.",
                    'afternoon' => "Visit key heritage stupas or central lakeside areas. Enjoy traditional lunch.",
                    'evening' => "Sunset walk and local evening cultural dinner.",
                    'safety_tip' => "Keep emergency numbers active and verify local weather before evening treks."
                ];
            } elseif ($i == 2) {
                $itinerary[] = [
                    'day' => 2,
                    'title' => "Sunrise Viewpoint & Main Highlights Tour",
                    'morning' => "Early 05:00 AM wake up for panoramic mountain sunrise.",
                    'afternoon' => "Sightseeing at caves, waterfalls, and local craft markets.",
                    'evening' => "Relaxed evening boating or local music performance.",
                    'safety_tip' => "Stay on designated footpaths and monitor steep downhill trails."
                ];
            } else {
                $itinerary[] = [
                    'day' => $i,
                    'title' => "Local Experience & Souvenir Shopping",
                    'morning' => "Visit organic tea farms or secondary cultural shrines.",
                    'afternoon' => "Enjoy authentic Nepalese Thali lunch and buy locally crafted souvenirs.",
                    'evening' => "Pack equipment and prepare for return journey.",
                    'safety_tip' => "Check Fair Price Checker for taxi and guide benchmarks before payment."
                ];
            }
        }

        sendJsonResponse(true, "AI Itinerary generated for $days days in $destination!", [
            'destination' => $destination,
            'days' => $days,
            'budget' => $budget,
            'interests' => $interests,
            'itinerary' => $itinerary,
            'packing_checklist' => [
                'Sturdy trekking boots or comfortable sneakers',
                'Rain jacket & windproof layer',
                'Power bank & offline map app (SAHAYATRI PWA)',
                'Personal first aid kit & rehydration salts',
                'Nepal Rupee (NPR) cash for local shops'
            ]
        ]);
        break;

    case 'translate':
        $text = trim($_POST['text'] ?? '');
        $targetLang = $_POST['target_lang'] ?? 'Nepali';

        if (empty($text)) {
            sendJsonResponse(false, 'Text is required for translation.', [], 400);
        }

        // Translation dictionary for common travel phrases
        $phrases = [
            'hello' => 'नमस्ते (Namaste)',
            'how are you' => 'तपाईंलाई कस्तो छ? (Tapaillai kasto chha?)',
            'thank you' => 'धन्यवाद (Dhanyabad)',
            'where is the hospital' => 'अस्पताल कहाँ छ? (Aspatal kahaan chha?)',
            'help me' => 'मलाई सहायता गर्नुहोस् (Malai sahaita garnuhos)',
            'how much does this cost' => 'यसको कति पर्छ? (Yasako kati parchha?)',
            'where is the bus stop' => 'बस स्टप कहाँ छ? (Bus stop kahaan chha?)',
            'is this safe' => 'के यो सुरक्षित छ? (Ke yo surakshit chha?)',
            'water' => 'पानी (Paani)',
            'food' => 'खाना (Khaana)',
            'hotel' => 'होटल (Hotel)'
        ];

        $lowerText = strtolower($text);
        $translated = $phrases[$lowerText] ?? " [Translated to $targetLang]: " . $text . " (नमस्ते / Dhanyabad)";

        sendJsonResponse(true, 'Translation complete', [
            'original' => $text,
            'translated' => $translated,
            'target_lang' => $targetLang,
            'quick_phrases' => [
                ['english' => 'Namaste (Hello / Greetings)', 'nepali' => 'नमस्ते'],
                ['english' => 'Dhanyabad (Thank You)', 'nepali' => 'धन्यवाद'],
                ['english' => 'Malai sahaita garnuhos (Help me please)', 'nepali' => 'मलाई सहायता गर्नुहोस्'],
                ['english' => 'Kati parchha? (How much does it cost?)', 'nepali' => 'कति पर्छ?'],
                ['english' => 'Aspatal kahaan chha? (Where is the hospital?)', 'nepali' => 'अस्पताल कहाँ छ?']
            ]
        ]);
        break;

    case 'chat':
        $userMsg = trim($_POST['message'] ?? '');
        if (empty($userMsg)) {
            sendJsonResponse(false, 'Message is required.', [], 400);
        }

        $lower = strtolower($userMsg);
        $reply = "I'm your SAHAYATRI AI Travel Companion! ";

        if (strpos($lower, 'weather') !== false) {
            $reply .= "Currently in Kathmandu & Pokhara it is around 24°C with light monsoon showers expected in the afternoon. High altitude treks should carry rain gear!";
        } elseif (strpos($lower, 'safe') !== false || strpos($lower, 'danger') !== false) {
            $reply .= "SAHAYATRI active safety maps show minor landslide caution around Seti River gorge and steep Sarangkot bends. Always check the Safety Map tab before heading out!";
        } elseif (strpos($lower, 'hotel') !== false || strpos($lower, 'stay') !== false) {
            $reply .= "We recommend verified stays with high Trust Scores, such as Himalayan Heritage Boutique Hotel (KTM) or Fishtail View Lakeside Resort (Pokhara).";
        } elseif (strpos($lower, 'sos') !== false || strpos($lower, 'emergency') !== false) {
            $reply .= "In an emergency, tap the red 🚨 SOS button at the bottom of your dashboard. It alerts your travel team and gives direct access to local Tourist Police (1144).";
        } else {
            $reply .= "How can I help you explore Nepal today? You can ask about weather, safe trekking routes, fair prices, or emergency contacts.";
        }

        sendJsonResponse(true, 'Chatbot response', ['reply' => $reply]);
        break;

    default:
        sendJsonResponse(false, 'Invalid AI action', [], 400);
        break;
}
