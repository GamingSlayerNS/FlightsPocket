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
        // Admin-only actions: verify admin before executing
        case 'admin_flights_tx_range':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) { $searchMessage = 'Admin privileges required.'; break; }
            $city = trim($_GET['city'] ?? '');
            $from = $_GET['from'] ?? '2024-09-01';
            $to = $_GET['to'] ?? '2024-10-31';
            if ($city === '') { $searchMessage = 'Provide a Texas city.'; break; }
            foreach ($bookings as $fb) {
                $flights = $fb['flights'] ?? [];
                foreach (['outbound','return'] as $leg) {
                    if (!isset($flights[$leg])) continue;
                    $dep = $flights[$leg]['departureDate'] ?? '';
                    $origin = $flights[$leg]['origin'] ?? '';
                    $dts = strtotime($dep);
                    if ($dts === false) continue;
                    if ($origin !== '' && stripos($origin, $city) !== false) {
                        if ($dts >= strtotime($from) && $dts <= strtotime($to)) {
                            $searchResults[] = $fb;
                            break 1;
                        }
                    }
                }
            }
            if (empty($searchResults)) $searchMessage = 'No flights found for that city/date range.';
            break;

        case 'admin_hotels_tx_range':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) { $searchMessage = 'Admin privileges required.'; break; }
            $city = trim($_GET['city'] ?? '');
            $from = $_GET['from'] ?? '2024-09-01';
            $to = $_GET['to'] ?? '2024-10-31';
            if ($city === '') { $searchMessage = 'Provide a Texas city.'; break; }
            foreach ($hotelBookings as $hb) {
                $hcity = $hb['hotel_city'] ?? $hb['city'] ?? '';
                $checkIn = $hb['checkIn_date'] ?? $hb['check_in'] ?? '';
                $ts = strtotime($checkIn);
                if ($hcity !== '' && stripos($hcity, $city) !== false) {
                    if ($ts !== false && $ts >= strtotime($from) && $ts <= strtotime($to)) {
                        $searchResults[] = $hb;
                    } elseif ($ts === false) {
                        if (stripos($checkIn, 'Sep') !== false || stripos($checkIn, 'Oct') !== false) $searchResults[] = $hb;
                    }
                }
            }
            if (empty($searchResults)) $searchMessage = 'No hotel bookings found for that city/date range.';
            break;

        case 'admin_top_hotels':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) { $searchMessage = 'Admin privileges required.'; break; }
            // return top N most expensive hotel bookings; default N=5
            $n = intval($_GET['n'] ?? 5);
            $list = [];
            foreach ($hotelBookings as $hb) {
                $priceRaw = $hb['total_price'] ?? $hb['totalPrice'] ?? '';
                $num = 0.0;
                if ($priceRaw !== '') {
                    $num = floatval(preg_replace('/[^0-9\.\-]/','', $priceRaw));
                }
                $list[] = ['hb'=>$hb,'price'=>$num];
            }
            usort($list, function($a,$b){ return ($b['price'] <=> $a['price']); });
            $top = array_slice($list,0,$n);
            foreach ($top as $t) $searchResults[] = $t['hb'];
            if (empty($searchResults)) $searchMessage = 'No hotel bookings available.';
            break;

        case 'admin_flights_with_infant':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) { $searchMessage = 'Admin privileges required.'; break; }
            foreach ($bookings as $fb) {
                $flights = $fb['flights'] ?? [];
                $hasInfant = false;
                foreach (['outbound','return'] as $leg) {
                    if (!isset($flights[$leg])) continue;
                    $dep = $flights[$leg]['departureDate'] ?? '';
                    foreach ($fb['passengers'] ?? [] as $p) {
                        $dob = $p['dob'] ?? $p['DateOfBirth'] ?? '';
                        $dts = strtotime($dep);
                        $bd = strtotime($dob);
                        if ($bd === false || $dts === false) continue;
                        $ageDays = ($dts - $bd) / 86400.0;
                        if ($ageDays < 365*2) { $hasInfant = true; break 2; }
                    }
                }
                if ($hasInfant) $searchResults[] = $fb;
            }
            if (empty($searchResults)) $searchMessage = 'No booked flights with infant passengers found.';
            break;

        case 'admin_flights_infant_and_5children':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) { $searchMessage = 'Admin privileges required.'; break; }
            foreach ($bookings as $fb) {
                $flights = $fb['flights'] ?? [];
                $match = false;
                foreach (['outbound','return'] as $leg) {
                    if (!isset($flights[$leg])) continue;
                    $dep = $flights[$leg]['departureDate'] ?? '';
                    $dts = strtotime($dep);
                    if ($dts === false) continue;
                    $infantFound = false; $childrenCount = 0;
                    foreach ($fb['passengers'] ?? [] as $p) {
                        $dob = $p['dob'] ?? $p['DateOfBirth'] ?? '';
                        $bd = strtotime($dob);
                        if ($bd === false) continue;
                        $ageYears = floor((($dts - $bd) / 86400.0) / 365.0);
                        if ($ageYears < 2) $infantFound = true;
                        if ($ageYears >=2 && $ageYears < 12) $childrenCount++;
                    }
                    if ($infantFound && $childrenCount >= 5) { $match = true; break; }
                }
                if ($match) $searchResults[] = $fb;
            }
            if (empty($searchResults)) $searchMessage = 'No booked flights found with infant + >=5 children.';
            break;

        case 'admin_top_flights':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) { $searchMessage = 'Admin privileges required.'; break; }
            $n = intval($_GET['n'] ?? 5);
            $list = [];
            foreach ($bookings as $fb) {
                $price = floatval($fb['totalPrice'] ?? $fb['total_price'] ?? 0);
                $list[] = ['b'=>$fb,'price'=>$price];
            }
            usort($list, function($a,$b){ return ($b['price'] <=> $a['price']); });
            $top = array_slice($list,0,$n);
            foreach ($top as $t) $searchResults[] = $t['b'];
            if (empty($searchResults)) $searchMessage = 'No flight bookings available.';
            break;

        case 'admin_flights_tx_no_infant':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) { $searchMessage = 'Admin privileges required.'; break; }
            $city = trim($_GET['city'] ?? '');
            if ($city === '') { $searchMessage = 'Provide a Texas city.'; break; }
            foreach ($bookings as $fb) {
                $flights = $fb['flights'] ?? [];
                foreach (['outbound','return'] as $leg) {
                    if (!isset($flights[$leg])) continue;
                    $dep = $flights[$leg]['departureDate'] ?? '';
                    $origin = $flights[$leg]['origin'] ?? '';
                    $dts = strtotime($dep);
                    if ($dts === false) continue;
                    if ($origin !== '' && stripos($origin, $city) !== false) {
                        // ensure no infant
                        $hasInfant = false;
                        foreach ($fb['passengers'] ?? [] as $p) {
                            $dob = $p['dob'] ?? $p['DateOfBirth'] ?? '';
                            $bd = strtotime($dob);
                            if ($bd === false) continue;
                            $ageDays = ($dts - $bd) / 86400.0;
                            if ($ageDays < 365*2) { $hasInfant = true; break; }
                        }
                        if (!$hasInfant) { $searchResults[] = $fb; break 1; }
                    }
                }
            }
            if (empty($searchResults)) $searchMessage = 'No matching flights without infants found.';
            break;

        case 'admin_count_flights_arrive_ca_months':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) { $searchMessage = 'Admin privileges required.'; break; }
            $city = trim($_GET['city'] ?? '');
            if ($city === '') { $searchMessage = 'Provide a California city.'; break; }
            $count = 0;
            foreach ($bookings as $fb) {
                $flights = $fb['flights'] ?? [];
                foreach (['outbound','return'] as $leg) {
                    if (!isset($flights[$leg])) continue;
                    $arr = $flights[$leg]['arrivalDate'] ?? '';
                    $dest = $flights[$leg]['destination'] ?? '';
                    $ts = strtotime($arr);
                    if ($ts === false) continue;
                    $ym = date('Y-m', $ts);
                    if (($ym === '2024-09' || $ym === '2024-10') && $dest !== '' && stripos($dest, $city) !== false) {
                        $count++;
                    }
                }
            }
            $searchResults = ['count'=>$count];
            break;
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
        case 'bookings_by_month':
            $month = $_GET['month'] ?? '';
            $year = $_GET['year'] ?? '';
            if ($month === '' || $year === '') {
                $searchMessage = 'Please provide both month and year.';
                break;
            }

            // Validate month and year
            if (!preg_match('/^(0[1-9]|1[0-2])$/', $month) || !preg_match('/^\d{4}$/', $year)) {
                $searchMessage = 'Invalid month or year format.';
                break;
            }

            $targetYm = "$year-$month";

            // Filter flight bookings by month and year
            foreach ($userBookings as $fb) {
                $added = false;
                $flights = $fb['flights'] ?? [];
                foreach (['outbound', 'return'] as $leg) {
                    if (isset($flights[$leg]['departureDate']) && str_starts_with($flights[$leg]['departureDate'], $targetYm)) {
                        $searchResults[] = ['type' => 'flight', 'booking' => $fb];
                        $added = true;
                        break;
                    }
                }
                if ($added) continue;
            }

            // Filter hotel bookings by month and year
            foreach ($userHotelBookings as $hb) {
                $checkIn = $hb['checkIn_date'] ?? $hb['check_in'] ?? '';
                $checkOut = $hb['checkOut_date'] ?? $hb['check_out'] ?? '';
                $found = false;
                foreach ([$checkIn, $checkOut] as $dstr) {
                    if (!$dstr) continue;
                    $ts = strtotime($dstr);
                    if ($ts !== false) {
                        if (date('Y-m', $ts) === $targetYm) {
                            $found = true;
                            break;
                        }
                    } else {
                        // Fallback: simple substring check
                        if (stripos($dstr, date('M', mktime(0, 0, 0, intval($month), 10))) !== false && stripos($dstr, $year) !== false) {
                            $found = true;
                            break;
                        }
                    }
                }
                if ($found) $searchResults[] = ['type' => 'hotel', 'booking' => $hb];
            }

            if (empty($searchResults)) {
                $searchMessage = "No bookings found for $year-$month in your account.";
            }
            break;
        default:
            $searchMessage = '';
    }
}

// end of backend file

