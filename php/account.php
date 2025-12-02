<?php
require_once __DIR__ . '/util/authentication.php';
requireAuth();

// expose $user for the including page
$user = $GLOBALS['user'] ?? null;

// Read flight bookings
$bookingFile = __DIR__ . '/../db/flight-booking.json';
$bookings = [];
if (file_exists($bookingFile)) {
    $content = file_get_contents($bookingFile);
    if ($content !== false) {
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded) && isset($decoded[0])) {
                $bookings = $decoded;
            } elseif (is_array($decoded)) {
                $bookings = [$decoded];
            }
        }
    }
}

// Filter bookings for this user. In our server-side booking flow we store userId as the phone number.
$userPhone = $user['PhoneNumber'] ?? '';
$userBookings = array_filter($bookings, function($b) use ($userPhone) {
    if (!isset($b['userId'])) return false;
    return (string)$b['userId'] === (string)$userPhone;
});

// Read hotel bookings (may be a single object or array)
$hotelFile = __DIR__ . '/../db/hotel-booking.json';
$hotelBookings = [];
if (file_exists($hotelFile)) {
    $cont = file_get_contents($hotelFile);
    if ($cont !== false) {
        $decodedH = json_decode($cont, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decodedH) && isset($decodedH[0])) {
                $hotelBookings = $decodedH;
            } elseif (is_array($decodedH)) {
                $hotelBookings = [$decodedH];
            }
        }
    }
}

// Filter hotel bookings for this user; hotel bookings may use 'user_id'
$userHotelBookings = array_filter($hotelBookings, function($hb) use ($userPhone) {
    if (!isset($hb['user_id'])) return false;
    return (string)$hb['user_id'] === (string)$userPhone;
});

// Handle retrieval actions from query params (GET)
$action = $_GET['action'] ?? '';
$searchResults = [];
$searchMessage = '';
if ($action) {
    switch ($action) {
        case 'flight_by_id':
            $id = $_GET['id'] ?? '';
            if ($id === '') { $searchMessage = 'Please provide a Flight booking id.'; break; }
            // find in user's flight bookings
            foreach ($userBookings as $fb) {
                if (isset($fb['bookingNumber']) && (string)$fb['bookingNumber'] === (string)$id) {
                    $searchResults[] = $fb;
                    break;
                }
            }
            if (empty($searchResults)) $searchMessage = 'No flight booking with that id found for your account.';
            break;
        case 'flight_passengers':
            $id = $_GET['id'] ?? '';
            if ($id === '') { $searchMessage = 'Please provide a Flight booking id.'; break; }
            foreach ($userBookings as $fb) {
                if (isset($fb['bookingNumber']) && (string)$fb['bookingNumber'] === (string)$id) {
                    $searchResults = $fb['passengers'] ?? [];
                    break;
                }
            }
            if (empty($searchResults)) $searchMessage = 'No passengers found for that flight booking id in your account.';
            break;
        case 'hotel_by_id':
            $id = $_GET['id'] ?? '';
            if ($id === '') { $searchMessage = 'Please provide a Hotel booking id.'; break; }
            foreach ($userHotelBookings as $hb) {
                if ((isset($hb['booking_number']) && (string)$hb['booking_number'] === (string)$id) || (isset($hb['bookingNumber']) && (string)$hb['bookingNumber'] === (string)$id)) {
                    $searchResults[] = $hb;
                    break;
                }
            }
            if (empty($searchResults)) $searchMessage = 'No hotel booking with that id found for your account.';
            break;
        case 'sep2024':
            // flights in Sep 2024
            foreach ($userBookings as $fb) {
                $added = false;
                $flights = $fb['flights'] ?? [];
                foreach (['outbound', 'return'] as $leg) {
                    if (isset($flights[$leg]['departureDate']) && str_starts_with($flights[$leg]['departureDate'], '2024-09')) {
                        $searchResults[] = ['type'=>'flight','booking'=>$fb];
                        $added = true; break;
                    }
                }
                if ($added) continue;
            }
            // hotels in Sep 2024 (checkIn/checkOut strings)
            foreach ($userHotelBookings as $hb) {
                $checkIn = $hb['checkIn_date'] ?? $hb['check_in'] ?? '';
                $checkOut = $hb['checkOut_date'] ?? $hb['check_out'] ?? '';
                $found = false;
                foreach ([$checkIn,$checkOut] as $dstr) {
                    if (!$dstr) continue;
                    $ts = strtotime($dstr);
                    if ($ts !== false) {
                        if (date('Y-m', $ts) === '2024-09') { $found = true; break; }
                    } else {
                        // fallback: simple substring check
                        if (stripos($dstr, 'Sep') !== false && stripos($dstr, '2024') !== false) { $found = true; break; }
                    }
                }
                if ($found) $searchResults[] = ['type'=>'hotel','booking'=>$hb];
            }
            if (empty($searchResults)) $searchMessage = 'No bookings found for Sep 2024 in your account.';
            break;
        case 'flight_by_ssn':
            $ssn = $_GET['ssn'] ?? '';
            if ($ssn === '') { $searchMessage = 'Please provide an SSN.'; break; }
            foreach ($userBookings as $fb) {
                $pass = $fb['passengers'] ?? [];
                foreach ($pass as $p) {
                    if (isset($p['ssn']) && (string)$p['ssn'] === (string)$ssn) {
                        $searchResults[] = $fb;
                        break 1;
                    }
                }
            }
            if (empty($searchResults)) $searchMessage = 'No flight bookings found for that SSN in your account.';
            break;
        default:
            $searchMessage = '';
    }
}

// end of backend file

