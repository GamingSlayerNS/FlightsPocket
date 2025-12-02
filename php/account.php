<?php
require_once 'util/authentication.php';
requireAuth();
// $GLOBALS['user'] is populated by requireAuth()
$user = isset($GLOBALS['user']) ? $GLOBALS['user'] : null;

// Read bookings
$bookingFile = __DIR__ . '/../db/flight-booking.json';
$bookings = [];
if (file_exists($bookingFile)) {
    $content = file_get_contents($bookingFile);
    if ($content !== false) {
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // bookings file may be an array or single record
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

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Account - Flights Pocket</title>
<link rel="stylesheet" href="/src/mystyle.css" />
</head>
<body>
<header>
    <button id="hamburger-menu">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </button>
    <h1>Flights Pocket</h1>
    <div id="datetime"></div>
</header>
<nav>
    <a href="/index.php" style="height: 17.5px"
        ><img
            src="/assets/Home Page Icon.png"
            alt="Home Icon"
            height="13px"
            style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 4.8px"
        />Home</a
    >
    <a href="/flights.php" style="height: 17.5px"
        ><img
            src="/assets/Flights Icon.png"
            alt="Flights Icon"
            height="15px"
            style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 5px"
        />Flights</a
    >
    <a href="/stays.php" style="height: 17.5px"
        ><img
            src="/assets/Stays Icon.png"
            alt="Stays Icon"
            height="15px"
            style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 5.8px"
        />Stays</a
    >
    <a href="/cars.php" style="height: 17.5px"
        ><img
            src="/assets/Cars Icon.png"
            alt="Cars Icon"
            height="17px"
            style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 4.2px"
        />Cars</a
    >
    <a href="/cruises.php" style="height: 17.5px"
        ><img
            src="/assets/Cruises Icon.png"
            alt="Cruises Icon"
            height="16px"
            style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 5px"
        />Cruises</a
    >
    <a href="/contact-us.php" style="height: 17.5px"
        ><img
            src="/assets/Contact Us Icon.png"
            alt="Contact Us Icon"
            height="16px"
            style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 4.75px; margin-left: -5px"
        />Contact Us</a
    >
    <a href="/cart.php" style="height: 17.5px"
        ><img
            src="/assets/cart.png"
            alt="Cart Icon"
            height="16px"
            style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 5px; margin-left: -5px"
        />Cart</a
    >
    <a href="/logout.php" style="height: 17.5px"
        ><img
            src="/assets/Logout.svg"
            alt="Logout Icon"
            height="16px"
            style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 5px; margin-left: -5px"
        />Logout</a
    >
</nav>
<main>
    <section class="container">
        <h2>Profile</h2>
        <?php if ($user): ?>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['PhoneNumber'] ?? ''); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars(trim(($user['FirstName'] ?? '') . ' ' . ($user['LastName'] ?? ''))); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['Email'] ?? ''); ?></p>
            <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($user['DateOfBirth'] ?? ''); ?></p>
            <p><strong>Gender:</strong> <?php echo htmlspecialchars($user['Gender'] ?? ''); ?></p>
            <?php if (isset($user['Admin']) && ($user['Admin'] == 1 || $user['Admin'] === '1')): ?>
                <p><strong>Role:</strong> Administrator</p>
                <section>
                    <h3>Admin: All bookings</h3>
                    <?php if (empty($bookings)): ?>
                        <p>No bookings recorded.</p>
                    <?php else: ?>
                        <ul>
                        <?php foreach ($bookings as $bb): ?>
                            <li><?php echo htmlspecialchars(($bb['bookingNumber'] ?? 'N/A') . ' — User: ' . ($bb['userId'] ?? '')); ?></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        <?php else: ?>
            <p>No user profile available.</p>
        <?php endif; ?>

        <section>
            <h3>Retrieve Bookings / Passengers</h3>
            <form method="get" style="margin-bottom:6px">
                <input type="hidden" name="action" value="flight_by_id" />
                <label>Flight Booking ID: <input name="id" placeholder="e.g. B1UR5MP" /></label>
                <button type="submit">Find Flight Booking</button>
            </form>

            <form method="get" style="margin-bottom:6px">
                <input type="hidden" name="action" value="flight_passengers" />
                <label>Flight Booking ID (Passengers): <input name="id" placeholder="e.g. B1UR5MP" /></label>
                <button type="submit">Show Passengers</button>
            </form>

            <form method="get" style="margin-bottom:6px">
                <input type="hidden" name="action" value="hotel_by_id" />
                <label>Hotel Booking ID: <input name="id" placeholder="e.g. B3372J6" /></label>
                <button type="submit">Find Hotel Booking</button>
            </form>

            <form method="get" style="margin-bottom:6px">
                <input type="hidden" name="action" value="sep2024" />
                <button type="submit">Show My Sep 2024 Bookings</button>
            </form>

            <form method="get" style="margin-bottom:6px">
                <input type="hidden" name="action" value="flight_by_ssn" />
                <label>Search my flights by passenger SSN: <input name="ssn" placeholder="123-45-6789" /></label>
                <button type="submit">Search by SSN</button>
            </form>

            <?php if ($searchMessage): ?><p><em><?php echo htmlspecialchars($searchMessage); ?></em></p><?php endif; ?>

            <?php if (!empty($searchResults)): ?>
                <div class="search-results">
                    <h4>Results</h4>
                    <?php if ($action === 'flight_passengers'): ?>
                        <ul>
                        <?php foreach ($searchResults as $p): ?>
                            <li><?php echo htmlspecialchars(($p['ssn'] ?? '') . ' — ' . ($p['firstName'] ?? '') . ' ' . ($p['lastName'] ?? '') . ' (' . ($p['dob'] ?? '') . ')'); ?></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php elseif ($action === 'sep2024'): ?>
                        <?php foreach ($searchResults as $entry): ?>
                            <?php if ($entry['type'] === 'flight'): $fb = $entry['booking']; ?>
                                <div class="booking">
                                    <h5>Flight Booking #: <?php echo htmlspecialchars($fb['bookingNumber'] ?? ''); ?></h5>
                                    <p><strong>Price:</strong> $<?php echo htmlspecialchars(number_format($fb['totalPrice'] ?? 0,2)); ?></p>
                                </div>
                            <?php else: $hb = $entry['booking']; ?>
                                <div class="booking">
                                    <h5>Hotel Booking #: <?php echo htmlspecialchars($hb['booking_number'] ?? $hb['bookingNumber'] ?? ''); ?></h5>
                                    <p><strong>Hotel city:</strong> <?php echo htmlspecialchars($hb['hotel_city'] ?? $hb['city'] ?? ''); ?></p>
                                    <p><strong>Check-in:</strong> <?php echo htmlspecialchars($hb['checkIn_date'] ?? $hb['check_in'] ?? ''); ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($searchResults as $res): ?>
                            <?php if (isset($res['flights']) || isset($res['passengers'])): ?>
                                <div class="booking">
                                    <h5>Booking #: <?php echo htmlspecialchars($res['bookingNumber'] ?? $res['booking_number'] ?? ''); ?></h5>
                                    <p><strong>Total:</strong> $<?php echo htmlspecialchars(number_format($res['totalPrice'] ?? $res['total_price'] ?? 0,2)); ?></p>
                                    <?php if (!empty($res['flights'])): ?>
                                        <?php if (isset($res['flights']['outbound'])): $out = $res['flights']['outbound']; ?>
                                            <div><strong>Outbound:</strong> <?php echo htmlspecialchars($out['flightId'] ?? ''); ?> — <?php echo htmlspecialchars(($out['origin'] ?? '') . ' → ' . ($out['destination'] ?? '')); ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php else: /* hotel booking object */ ?>
                                <div class="booking">
                                    <h5>Hotel Booking #: <?php echo htmlspecialchars($res['booking_number'] ?? $res['bookingNumber'] ?? ''); ?></h5>
                                    <p><strong>City:</strong> <?php echo htmlspecialchars($res['hotel_city'] ?? $res['city'] ?? ''); ?></p>
                                    <p><strong>Check-in:</strong> <?php echo htmlspecialchars($res['checkIn_date'] ?? $res['check_in'] ?? ''); ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <h2>Your Bookings</h2>
        <?php if (empty($userBookings)): ?>
            <p>You have no bookings yet.</p>
        <?php else: ?>
            <?php foreach ($userBookings as $b): ?>
                <div class="booking">
                    <h3>Booking #: <?php echo htmlspecialchars($b['bookingNumber'] ?? 'N/A'); ?></h3>
                    <p><strong>Total Price:</strong> $<?php echo htmlspecialchars(number_format($b['totalPrice'] ?? 0, 2)); ?></p>
                    <?php if (!empty($b['flights'])): ?>
                        <?php if (isset($b['flights']['outbound'])): $out = $b['flights']['outbound']; ?>
                            <div><strong>Outbound:</strong> <?php echo htmlspecialchars($out['flightId'] ?? ''); ?> — <?php echo htmlspecialchars(($out['origin'] ?? '') . ' → ' . ($out['destination'] ?? '')); ?>
                            <br />Departure: <?php echo htmlspecialchars(($out['departureDate'] ?? '') . ' ' . ($out['departureTime'] ?? '')); ?>
                            <br />Arrival: <?php echo htmlspecialchars(($out['arrivalDate'] ?? '') . ' ' . ($out['arrivalTime'] ?? '')); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($b['flights']['return'])): $ret = $b['flights']['return']; ?>
                            <div><strong>Return:</strong> <?php echo htmlspecialchars($ret['flightId'] ?? ''); ?> — <?php echo htmlspecialchars(($ret['origin'] ?? '') . ' → ' . ($ret['destination'] ?? '')); ?>
                            <br />Departure: <?php echo htmlspecialchars(($ret['departureDate'] ?? '') . ' ' . ($ret['departureTime'] ?? '')); ?>
                            <br />Arrival: <?php echo htmlspecialchars(($ret['arrivalDate'] ?? '') . ' ' . ($ret['arrivalTime'] ?? '')); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($b['passengers'])): ?>
                        <h4>Passengers</h4>
                        <ul>
                        <?php foreach ($b['passengers'] as $p): ?>
                            <li><?php echo htmlspecialchars(($p['ssn'] ?? '') . ' — ' . ($p['firstName'] ?? '') . ' ' . ($p['lastName'] ?? '') . ' (' . ($p['dob'] ?? '') . ')'); ?></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
<footer>
    <p>&copy; 2025 Flights Pocket</p>
</footer>
</body>
</html>
