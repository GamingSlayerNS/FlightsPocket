<?php
require_once 'php/account.php';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Account - Flights Pocket</title>
    <link rel="stylesheet" href="src/mystyle.css" />
    <script src="hooks/app.js"></script>
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
            <a href="index.php" style="height: 17.5px">Home</a>
            <a href="flights.php" style="height: 17.5px">Flights</a>
            <a href="stays.php" style="height: 17.5px">Stays</a>
            <a href="cars.php" style="height: 17.5px">Cars</a>
            <a href="cruises.php" style="height: 17.5px">Cruises</a>
            <a href="contact-us.php" style="height: 17.5px">Contact Us</a>
            <a href="cart.php" style="height: 17.5px">Cart</a>
            <a href="account.php" style="height: 17.5px">Profile</a>
            <a href="logout.php" style="height: 17.5px">Logout</a>
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
                        <section>
                            <h3>Admin: Queries</h3>
                            <form method="get" style="margin-bottom:6px">
                                <input type="hidden" name="action" value="admin_flights_tx_range" />
                                <label>Texas City: <input name="city" placeholder="e.g. Dallas" /></label>
                                <label>From: <input type="date" name="from" value="2024-09-01" /></label>
                                <label>To: <input type="date" name="to" value="2024-10-31" /></label>
                                <button type="submit">Flights departing TX (Sep-Oct)</button>
                            </form>

                            <form method="get" style="margin-bottom:6px">
                                <input type="hidden" name="action" value="admin_hotels_tx_range" />
                                <label>Texas City: <input name="city" placeholder="e.g. Dallas" /></label>
                                <label>From: <input type="date" name="from" value="2024-09-01" /></label>
                                <label>To: <input type="date" name="to" value="2024-10-31" /></label>
                                <button type="submit">Hotels in TX (Sep-Oct)</button>
                            </form>

                            <form method="get" style="margin-bottom:6px">
                                <input type="hidden" name="action" value="admin_top_hotels" />
                                <label>Top N: <input name="n" type="number" min="1" max="50" value="5" /></label>
                                <button type="submit">Most expensive booked hotels</button>
                            </form>

                            <form method="get" style="margin-bottom:6px">
                                <input type="hidden" name="action" value="admin_flights_with_infant" />
                                <button type="submit">All booked flights with an infant passenger</button>
                            </form>

                            <form method="get" style="margin-bottom:6px">
                                <input type="hidden" name="action" value="admin_flights_infant_and_5children" />
                                <button type="submit">Flights with infant and ≥5 children</button>
                            </form>

                            <form method="get" style="margin-bottom:6px">
                                <input type="hidden" name="action" value="admin_top_flights" />
                                <label>Top N: <input name="n" type="number" min="1" max="50" value="5" /></label>
                                <button type="submit">Most expensive booked flights</button>
                            </form>

                            <form method="get" style="margin-bottom:6px">
                                <input type="hidden" name="action" value="admin_flights_tx_no_infant" />
                                <label>Texas City: <input name="city" placeholder="e.g. Dallas" /></label>
                                <button type="submit">Flights from TX with no infant passenger</button>
                            </form>

                            <form method="get" style="margin-bottom:6px">
                                <input type="hidden" name="action" value="admin_count_flights_arrive_ca_months" />
                                <label>California City: <input name="city" placeholder="e.g. Los Angeles" /></label>
                                <button type="submit">Count flights arriving CA (Sep/Oct 2024)</button>
                            </form>
                        </section>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No user profile available.</p>
                <?php endif; ?>

                <section>
                    <h3>Retrieve Bookings / Passengers</h3>
                    <form method="get" style="margin-bottom:32px">
                        <input type="hidden" name="action" value="flight_by_id" />
                        <label>Flight Booking ID: <input name="id" placeholder="e.g. B1UR5MP" /></label>
                        <button type="submit">Find Flight Booking</button>
                    </form>

                    <form method="get" style="margin-bottom:32px">
                        <input type="hidden" name="action" value="flight_passengers" />
                        <label>Flight Booking ID (Passengers): <input name="id" placeholder="e.g. B1UR5MP" /></label>
                        <button type="submit">Show Passengers</button>
                    </form>

                    <form method="get" style="margin-bottom:32px">
                        <input type="hidden" name="action" value="hotel_by_id" />
                        <label>Hotel Booking ID: <input name="id" placeholder="e.g. B3372J6" /></label>
                        <button type="submit">Find Hotel Booking</button>
                    </form>

                    <form method="get" style="margin-bottom:32px">
                        <input type="hidden" name="action" value="bookings_by_month" />
                        <div style="display: flex; flex-direction: row;">
                            <label for="month">Select Month:</label>
                            <select name="month" id="month" style="margin-left: 8px">
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09" selected>September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div style="display: flex; flex-direction: row;">
                            <label for="year">Select Year:</label>
                            <select name="year" id="year" style="margin-left: 8px">
                                <option value="2024" selected>2024</option>
                                <option value="2025">2025</option>
                            </select>
                        </div>
                        <button type="submit">Show Bookings</button>
                    </form>

                    <form method="get" style="margin-bottom:32px">
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
                            <?php elseif ($action === 'admin_count_flights_arrive_ca_months' && isset($searchResults['count'])): ?>
                                <p><strong>Count:</strong> <?php echo (int)$searchResults['count']; ?></p>
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

                <div style="width: 100%; height: 3px; border-bottom: 3px solid #787878ff; margin: 20px 0;"></div>

                <h2>Your Bookings:</h2>
                <?php if (empty($userBookings)): ?>
                    <p>You have no bookings yet.</p>
                <?php else: ?>
                    <?php foreach ($userBookings as $b): ?>
                        <div class="booking">
                            <h3>Booking #: <?php echo htmlspecialchars($b['bookingNumber'] ?? 'N/A'); ?></h3>
                            <div  style="margin-left: 32px;">

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
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                <?php if (isset($user['Admin']) && ($user['Admin'] == 1 || $user['Admin'] === '1')): ?>
                    <div style="width: 100%; height: 3px; border-bottom: 3px solid #787878ff; margin: 20px 0;"></div>

                    <section>
                        <form method="post" enctype="multipart/form-data" action="php/upload-flights.php" style="margin-bottom:6px">
                            <label for="flights-json">Upload Flights JSON:</label>
                            <input type="file" name="flights-json" id="flights-json" accept="application/json" required />
                            <button type="submit">Upload</button>
                        </form>
                    </section>

                    <div style="width: 100%; height: 3px; border-bottom: 3px solid #787878ff; margin: 20px 0;"></div>

                    <section>
                        <form method="post" enctype="multipart/form-data" action="php/upload-hotels.php" style="margin-bottom:6px">
                            <label for="hotels-xml">Upload Hotels XML:</label>
                            <input type="file" name="hotels-xml" id="hotels-xml" accept="application/xml" required />
                            <button type="submit">Upload</button>
                        </form>
                    </section>
                <?php endif; ?>
            </section>

            </main>
        <footer>
            <p>&copy; 2025 Flights Pocket</p>
        </footer>
    </body>
</html>
