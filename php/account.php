<?php
require_once __DIR__ . '/util/authentication.php';
requireAuth();
require_once __DIR__ . '/util/db.php'; // Include database connection

$db = createMysqli(); // Initialize the database connection

// expose $user for the including page
$user = $GLOBALS['user'] ?? null;

// Read flight bookings from JSON
$bookingFile = __DIR__ . '/../db/flight-booking.json';
$jsonBookings = [];
if (file_exists($bookingFile)) {
    $content = file_get_contents($bookingFile);
    if ($content !== false) {
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded) && isset($decoded[0])) {
                $jsonBookings = $decoded;
            } elseif (is_array($decoded)) {
                $jsonBookings = [$decoded];
            }
        }
    }
}

// Fetch flight bookings from SQL
$sqlBookings = [];
$stmt = $db->prepare("SELECT * FROM FlightBookings");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sqlBookings[] = $row;
}
$stmt->close();

// Combine JSON and SQL flight bookings
$allFlightBookings = array_merge($jsonBookings, $sqlBookings);

// Read hotel bookings from JSON
$hotelFile = __DIR__ . '/../db/hotel-booking.json';
$jsonHotelBookings = [];
if (file_exists($hotelFile)) {
    $cont = file_get_contents($hotelFile);
    if ($cont !== false) {
        $decodedH = json_decode($cont, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decodedH) && isset($decodedH[0])) {
                $jsonHotelBookings = $decodedH;
            } elseif (is_array($decodedH)) {
                $jsonHotelBookings = [$decodedH];
            }
        }
    }
}

// Fetch hotel bookings from SQL
$sqlHotelBookings = [];
$stmt = $db->prepare("SELECT * FROM HotelBookings");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sqlHotelBookings[] = $row;
}
$stmt->close();

// Combine JSON and SQL hotel bookings
$allHotelBookings = array_merge($jsonHotelBookings, $sqlHotelBookings);

// Display all bookings without filtering by user
$userBookings = $allFlightBookings;
$userHotelBookings = $allHotelBookings;
$adminAllBookings =  array_merge($allFlightBookings, $allHotelBookings);
$bookings = $allFlightBookings;

// Initialize adminResults and adminMessage
$adminResults = [
    'columns' => [], // Column names for the frontend
    'data' => []    // Actual data rows
];
$adminMessage = '';

// Handle retrieval actions from query params (GET)
$action = $_GET['action'] ?? '';
$searchResults = [
    'columns' => [], // Column names for the frontend
    'data' => []    // Actual data rows
];
$searchMessage = '';
if ($action) {
    switch ($action) {
        // Admin-only actions: verify admin before executing
        case 'admin_flights_tx_range':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) {
                $adminMessage = 'Admin privileges required.';
                break;
            }

            $city = trim($_GET['city'] ?? '');
            $from = $_GET['from'] ?? '2024-09-01';
            $to = $_GET['to'] ?? '2024-10-31';

            if ($city === '') {
                $adminMessage = 'Provide a Texas city.';
                break;
            }

            $stmt = $db->prepare(
                "SELECT fb.FlightBookingID, fb.TotalPrice, f.FlightID, f.DepartureDate, f.Origin, f.Destination
                FROM FlightBookings fb
                JOIN Flights f ON fb.FlightID = f.FlightID
                WHERE f.Origin LIKE ? AND f.DepartureDate BETWEEN ? AND ?"
            );

            $cityParam = "%$city%";
            $stmt->bind_param('sss', $cityParam, $from, $to);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                if (empty($adminResults['columns'])) {
                    $adminResults['columns'] = array_keys($row); // Set columns dynamically
                }
                $adminResults['data'][] = $row;
            }

            $stmt->close();

            if (empty($adminResults['data'])) {
                $adminMessage = 'No flights found for that city/date range.';
            }
            break;

        case 'admin_hotels_tx_range':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) {
                $adminMessage = 'Admin privileges required.';
                break;
            }

            $city = trim($_GET['city'] ?? '');
            $from = $_GET['from'] ?? '2024-09-01';
            $to = $_GET['to'] ?? '2024-10-31';
            $cityParam = "%$city%";

            if ($city === '') {
                $adminMessage = 'Provide a Texas city.';
                break;
            }

            $stmt = $db->prepare(
                "SELECT hb.HotelBookingID, hb.TotalPrice, hb.CheckInDate, hb.CheckOutDate, h.HotelName, h.City
                FROM HotelBookings hb
                JOIN Hotels h ON hb.HotelID = h.HotelID
                WHERE h.City LIKE ? AND (
                    (hb.CheckInDate BETWEEN ? AND ?)
                    OR (hb.CheckOutDate BETWEEN ? AND ?)
                )"
            );

            $stmt->bind_param('sssss', $cityParam, $from, $to, $from, $to);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                if (empty($adminResults['columns'])) {
                    $adminResults['columns'] = array_keys($row);
                }
                $adminResults['data'][] = $row;
            }

            $stmt->close();

            if (empty($adminResults['data'])) {
                $adminMessage = 'No hotel bookings found for that city/date range.';
            }
            break;

        case 'admin_top_hotels':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) {
                $adminMessage = 'Admin privileges required.';
                break;
            }

            $n = intval($_GET['n'] ?? 5);

            $stmt = $db->prepare(
                "SELECT hb.HotelBookingID, hb.TotalPrice, hb.CheckInDate, hb.CheckOutDate, h.HotelName, h.City
                FROM HotelBookings hb
                JOIN Hotels h ON hb.HotelID = h.HotelID
                ORDER BY hb.TotalPrice DESC
                LIMIT ?"
            );

            $stmt->bind_param('i', $n);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                if (empty($adminResults['columns'])) {
                    $adminResults['columns'] = array_keys($row);
                }
                $adminResults['data'][] = $row;
            }

            $stmt->close();

            if (empty($adminResults['data'])) {
                $adminMessage = 'No hotel bookings available.';
            }
            break;

        case 'admin_flights_with_infant':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) {
                $adminMessage = 'Admin privileges required.';
                break;
            }

            $stmt = $db->prepare(
                "SELECT fb.FlightBookingID, fb.TotalPrice, f.FlightID, f.DepartureDate, f.Origin, f.Destination
                FROM FlightBookings fb
                JOIN Flights f ON fb.FlightID = f.FlightID
                JOIN Passengers p ON fb.FlightBookingID = p.FlightBookingID
                WHERE TIMESTAMPDIFF(YEAR, p.DateOfBirth, f.DepartureDate) < 2"
            );

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                if (empty($adminResults['columns'])) {
                    $adminResults['columns'] = array_keys($row);
                }
                $adminResults['data'][] = $row;
            }

            $stmt->close();

            if (empty($adminResults['data'])) {
                $adminMessage = 'No booked flights with infant passengers found.';
            }
            break;

        case 'admin_flights_infant_and_5children':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) {
                $adminMessage = 'Admin privileges required.';
                break;
            }

            $stmt = $db->prepare(
                "SELECT fb.FlightBookingID, fb.TotalPrice, f.FlightID, f.DepartureDate, f.Origin, f.Destination
                FROM FlightBookings fb
                JOIN Flights f ON fb.FlightID = f.FlightID
                JOIN Passengers p ON fb.FlightBookingID = p.FlightBookingID
                GROUP BY fb.FlightBookingID
                HAVING SUM(TIMESTAMPDIFF(YEAR, p.DateOfBirth, f.DepartureDate) < 2) > 0
                   AND SUM(TIMESTAMPDIFF(YEAR, p.DateOfBirth, f.DepartureDate) BETWEEN 2 AND 12) >= 5"
            );

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                if (empty($adminResults['columns'])) {
                    $adminResults['columns'] = array_keys($row);
                }
                $adminResults['data'][] = $row;
            }

            $stmt->close();

            if (empty($adminResults['data'])) {
                $adminMessage = 'No booked flights found with infant + >=5 children.';
            }
            break;

        case 'admin_top_flights':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) {
                $adminMessage = 'Admin privileges required.';
                break;
            }

            $n = intval($_GET['n'] ?? 5);

            $stmt = $db->prepare(
                "SELECT fb.FlightBookingID, fb.TotalPrice, f.FlightID, f.DepartureDate, f.Origin, f.Destination
                FROM FlightBookings fb
                JOIN Flights f ON fb.FlightID = f.FlightID
                ORDER BY fb.TotalPrice DESC
                LIMIT ?"
            );

            $stmt->bind_param('i', $n);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                if (empty($adminResults['columns'])) {
                    $adminResults['columns'] = array_keys($row);
                }
                $adminResults['data'][] = $row;
            }

            $stmt->close();

            if (empty($adminResults['data'])) {
                $adminMessage = 'No flight bookings available.';
            }
            break;

        case 'admin_flights_tx_no_infant':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) {
                $adminMessage = 'Admin privileges required.';
                break;
            }

            $city = trim($_GET['city'] ?? '');

            if ($city === '') {
                $adminMessage = 'Provide a Texas city.';
                break;
            }

            $stmt = $db->prepare(
                "SELECT fb.FlightBookingID, fb.TotalPrice, f.FlightID, f.DepartureDate, f.Origin, f.Destination
                FROM FlightBookings fb
                JOIN Flights f ON fb.FlightID = f.FlightID
                WHERE f.Origin LIKE ?
                  AND fb.FlightBookingID NOT IN (
                      SELECT fb.FlightBookingID
                      FROM FlightBookings fb
                      JOIN Passengers p ON fb.FlightBookingID = p.FlightBookingID
                      WHERE TIMESTAMPDIFF(YEAR, p.DateOfBirth, f.DepartureDate) < 2
                  )"
            );

            $cityParam = "%$city%";
            $stmt->bind_param('s', $cityParam);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                if (empty($adminResults['columns'])) {
                    $adminResults['columns'] = array_keys($row);
                }
                $adminResults['data'][] = $row;
            }

            $stmt->close();

            if (empty($adminResults['data'])) {
                $adminMessage = 'No matching flights without infants found.';
            }
            break;

        case 'admin_count_flights_arrive_ca_months':
            if (!isset($user['Admin']) || ($user['Admin'] != 1 && $user['Admin'] !== '1')) {
                $adminMessage = 'Admin privileges required.';
                break;
            }

            $city = trim($_GET['city'] ?? '');

            if ($city === '') {
                $adminMessage = 'Provide a California city.';
                break;
            }

            $stmt = $db->prepare(
                "SELECT COUNT(*) AS FlightCount
                FROM FlightBookings fb
                JOIN Flights f ON fb.FlightID = f.FlightID
                WHERE f.Destination LIKE ?
                  AND (MONTH(f.ArrivalDate) IN (9, 10) AND YEAR(f.ArrivalDate) = 2024)"
            );

            $cityParam = "%$city%";
            $stmt->bind_param('s', $cityParam);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $adminResults['columns'] = array_keys($row);
                $adminResults['data'][] = $row;
            }

            $stmt->close();

            if (empty($adminResults['data'])) {
                $adminMessage = 'No flights found arriving in the specified city during the given months.';
            }
            break;
        case 'flight_by_id':
            $id = $_GET['id'] ?? '';
            if ($id === '') {
                $searchMessage = 'Please provide a Flight booking ID.';
                break;
            }

            // Fetch flight booking from SQL
            $stmt = $db->prepare("SELECT * FROM FlightBookings WHERE FlightBookingID = ?");
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $searchResults['columns'] = array_keys($row); // Extract column names
                $searchResults['data'][] = $row; // Add the result to data
            } else {
                $searchMessage = 'No flight booking found with the provided ID.';
            }
            $stmt->close();
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
            if ($id === '') {
                $searchMessage = 'Please provide a Hotel booking id.';
                break;
            }
            // Fetch hotel booking from SQL
            $stmt = $db->prepare("SELECT HotelBookingID, HotelID, CheckInDate, CheckOutDate, NumberOfRooms, PricePerNight, TotalPrice FROM HotelBookings WHERE HotelBookingID = ?");
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $searchResults['columns'] = array_keys($row); // Extract column names
                $searchResults['data'][] = $row; // Add the result to data
            } else {
                $searchMessage = 'No hotel booking with that id found.';
            }
            $stmt->close();
            break;

        case 'sep2024':
            $searchResults = [];

            // Filter flight bookings for September 2024
            foreach ($allFlightBookings as $flight) {
                $departureDate = $flight['DepartureDate'] ?? '';
                if (strtotime($departureDate) >= strtotime('2024-09-01') && strtotime($departureDate) <= strtotime('2024-09-30')) {
                    $searchResults[] = [
                        'type' => 'Flight',
                        'booking' => $flight
                    ];
                }
            }

            // Filter hotel bookings for September 2024
            foreach ($allHotelBookings as $hotel) {
                $checkInDate = $hotel['CheckInDate'] ?? '';
                $checkOutDate = $hotel['CheckOutDate'] ?? '';
                if (
                    (strtotime($checkInDate) >= strtotime('2024-09-01') && strtotime($checkInDate) <= strtotime('2024-09-30')) ||
                    (strtotime($checkOutDate) >= strtotime('2024-09-01') && strtotime($checkOutDate) <= strtotime('2024-09-30'))
                ) {
                    $searchResults[] = [
                        'type' => 'Hotel',
                        'booking' => $hotel
                    ];
                }
            }

            if (empty($searchResults)) {
                $searchMessage = 'No bookings found for September 2024.';
            }
            break;

        case 'flight_by_ssn':
            $ssn = $_GET['ssn'] ?? '';
            if ($ssn === '') { $searchMessage = 'Please provide an SSN.'; break; }
            foreach ($userBookings as $fb) {
                $pass = $fb['passengers'] ?? [];
                foreach ($pass as $p) {
                    if (isset($p['ssn']) && (string)$p['ssn'] === (string)$ssn) {
                        $searchResults['data'][] = $fb;
                        break 1;
                    }
                }
            }
            if (empty($searchResults['data'])) $searchMessage = 'No flight bookings found for that SSN in your account.';
            break;

        case 'bookings_by_month':
            $month = str_pad($_GET['month'] ?? '', 2, '0', STR_PAD_LEFT);
            $year = $_GET['year'] ?? '';
            $searchResults['columns'] = []; // Initialize columns
            $searchResults['data'] = [];

            if ($month && $year) {
                // Fetch flight bookings for the selected month and year
                $stmt = $db->prepare(
                    "SELECT fb.FlightBookingID, fb.TotalPrice, f.FlightID, f.DepartureDate, f.Origin, f.Destination
                    FROM FlightBookings fb
                    JOIN Flights f ON fb.FlightID = f.FlightID
                    WHERE YEAR(f.DepartureDate) = ? AND MONTH(f.DepartureDate) = ?"
                );
                $stmt->bind_param('ss', $year, $month);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    if (empty($searchResults['columns'])) {
                        $searchResults['columns'] = array_keys($row); // Set columns dynamically
                    }
                    $searchResults['data'][] = $row; // Add row data
                }
                $stmt->close();

                // Fetch hotel bookings for the selected month and year
                $stmt = $db->prepare(
                    "SELECT hb.HotelBookingID, hb.TotalPrice, hb.CheckInDate, hb.CheckOutDate, h.HotelName, h.City
                    FROM HotelBookings hb
                    JOIN Hotels h ON hb.HotelID = h.HotelID
                    WHERE (YEAR(hb.CheckInDate) = ? AND MONTH(hb.CheckInDate) = ?)
                       OR (YEAR(hb.CheckOutDate) = ? AND MONTH(hb.CheckOutDate) = ?)"
                );
                $stmt->bind_param('ssss', $year, $month, $year, $month);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    if (empty($searchResults['columns'])) {
                        $searchResults['columns'] = array_keys($row); // Set columns dynamically
                    }
                    $searchResults['data'][] = $row; // Add row data
                }
                $stmt->close();
            }

            if (empty($searchResults['data'])) {
                $searchMessage = 'No bookings found for the selected month and year.';
            }
            break;
    }
}

// For admin: provide all bookings (flights + hotels) for a specific user
if (isset($user['Admin']) && $user['Admin'] == 1 && isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    $userId = $db->real_escape_string($userId);

    // Fetch flight bookings for the user
    $stmt = $db->prepare("SELECT * FROM FlightBookings WHERE UserID = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $searchResults[] = $row;
    }
    $stmt->close();

    // Fetch hotel bookings for the user
    $stmt = $db->prepare("SELECT * FROM HotelBookings WHERE UserID = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $searchResults[] = $row;
    }
    $stmt->close();
}


