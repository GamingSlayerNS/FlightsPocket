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
            <a href="index.php" style="height: 17.5px"
                ><img
                    src="assets/Home Page Icon.png"
                    alt="Home Icon"
                    height="13px"
                    style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 4.8px"
                />Home</a
            >
            <a href="flights.php" style="height: 17.5px"
                ><img
                    src="assets/Flights Icon.png"
                    alt="Flights Icon"
                    height="15px"
                    style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 5px"
                />Flights</a
            >
            <a href="stays.php" style="height: 17.5px"
                ><img
                    src="assets/Stays Icon.png"
                    alt="Stays Icon"
                    height="15px"
                    style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 5.8px"
                />Stays</a
            >
            <a href="cars.php" style="height: 17.5px"
                ><img
                    src="assets/Cars Icon.png"
                    alt="Cars Icon"
                    height="17px"
                    style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 4.2px"
                />Cars</a
            >
            <a href="cruises.php" style="height: 17.5px"
                ><img
                    src="assets/Cruises Icon.png"
                    alt="Cruises Icon"
                    height="16px"
                    style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 5px"
                />Cruises</a
            >
            <a href="contact-us.php" style="height: 17.5px"
                ><img
                    src="assets/Contact Us Icon.png"
                    alt="Contact Us Icon"
                    height="16px"
                    style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 4.75px; margin-left: -5px"
                />Contact Us</a
            >
            <a href="cart.php" style="height: 17.5px"
                ><img
                    src="assets/cart.png"
                    alt="Cart Icon"
                    height="16px"
                    style="padding: 0px 10px 0px 0px; vertical-align: middle; margin-bottom: 5px; margin-left: -5px"
                />Cart</a
            >
            <a href="account.php" style="height: 17.5px">Profile</a>
            <a href="logout.php" style="height: 17.5px"
                ><img
                    src="assets/Logout.svg"
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
                    <?php endif; ?>
                <?php else: ?>
                    <p>No user profile available.</p>
                <?php endif; ?>

                <section class="User-queries">
                    <h2>Search Bookings By</h2>
                    <form method="get" style="margin-bottom:32px">
                        <input type="hidden" name="action" value="flight_by_id" />
                        <label>Flight Booking ID: <input name="id" placeholder="e.g. B1UR5MP" /></label>
                        <button type="submit">Find Flight Booking</button>
                    </form>

                    <form method="get" style="margin-bottom:32px">
                        <input type="hidden" name="action" value="hotel_by_id" />
                        <label>Hotel Booking ID: <input name="id" placeholder="e.g. B3372J6" /></label>
                        <button type="submit">Find Hotel Booking</button>
                    </form>

                    <form method="get" style="margin-bottom:32px">
                        <input type="hidden" name="action" value="flight_passengers" />
                        <label>Flight Booking ID (Passengers): <input name="id" placeholder="e.g. B1UR5MP" /></label>
                        <button type="submit">Show Passengers</button>
                    </form>

                    <form method="get" style="margin-bottom:32px">
                        <input type="hidden" name="action" value="flight_by_ssn" />
                        <label>SSN: <input name="ssn" pattern="[0-9]{3}-[0-9]{2}-[0-9]{4}" oninvalid="this.setCustomValidity('Please enter in the format xxx-xx-xxxx.')" placeholder="e.g. 123-45-6789" /></label>
                        <button type="submit">Show Flight Bookings</button>
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
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div style="display: flex; flex-direction: row;">
                            <label for="year">Select Year:</label>
                            <select name="year" id="year" style="margin-left: 8px">
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
                            </select>
                        </div>
                        <button type="submit">Show Bookings</button>
                    </form>

                    <h3>Search Results:</h3>
                    <?php if ($searchMessage): ?>
                        <p><em><?php echo htmlspecialchars($searchMessage); ?></em></p>
                    <?php endif; ?>

                    <?php if (!empty($searchResults['data'])): ?>
                        <table border="1" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <?php foreach ($searchResults['columns'] as $header): ?>
                                        <th><?php echo htmlspecialchars($header); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($searchResults['data'] as $result): ?>
                                    <tr>
                                        <?php foreach ($result as $value): ?>
                                            <td><?php echo htmlspecialchars($value); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>

                <div style="width: 100%; height: 3px; border-bottom: 3px solid #787878ff; margin: 20px 0;"></div>

                <?php if (isset($user['Admin']) && ($user['Admin'] == 1 || $user['Admin'] === '1')): ?>
                    <section>
                        <h3>Admin: All Bookings</h3>

                        <h4>Flight Bookings</h4>
                        <?php if (empty($allFlightBookings)): ?>
                            <p>No flight bookings recorded.</p>
                        <?php else: ?>
                            <table border="1" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th>Booking Number</th>
                                        <!-- <th>User ID</th> -->
                                        <th>Total Price</th>
                                        <th>Outbound Flight</th>
                                        <th>Return Flight</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allFlightBookings as $fb): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($fb['bookingNumber'] ?? $fb['FlightBookingID'] ?? 'N/A'); ?></td>
                                            <!-- <td><?php echo htmlspecialchars($fb['userId'] ?? 'N/A'); ?></td> -->
                                            <td>$<?php echo htmlspecialchars(number_format($fb['totalPrice'] ?? $fb['TotalPrice'] ?? 0, 2)); ?></td>
                                            <td><?php echo htmlspecialchars(($fb['outbound']['flightId'] ?? $fb['FlightID'] ?? '') . ' (' . ($fb['outbound']['origin'] ?? $fb['Origin'] ?? '') . ' → ' . ($fb['outbound']['destination'] ?? $fb['Destination'] ?? '') . ')'); ?></td>
                                            <td><?php echo htmlspecialchars(($fb['return']['flightId'] ?? '') . ' (' . ($fb['return']['origin'] ?? '') . ' → ' . ($fb['return']['destination'] ?? '') . ')'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <h4>Hotel Bookings</h4>
                        <?php if (empty($allHotelBookings)): ?>
                            <p>No hotel bookings recorded.</p>
                        <?php else: ?>
                            <table border="1" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th>Booking Number</th>
                                        <!--<th>User ID</th>-->
                                        <th>Hotel Name</th>
                                        <th>City</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Total Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allHotelBookings as $hb): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($hb['booking_number'] ?? $hb['HotelBookingID'] ?? 'N/A'); ?></td>
                                            <!--<td><?php echo htmlspecialchars($hb['user_id'] ?? 'N/A'); ?></td>-->
                                            <td><?php echo htmlspecialchars($hb['hotel_name'] ?? $hb['HotelName'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($hb['hotel_city'] ?? $hb['City'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($hb['checkIn_date'] ?? $hb['CheckInDate'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($hb['checkOut_date'] ?? $hb['CheckOutDate'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($hb['total_price'] ?? $hb['TotalPrice'] ?? 0, 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </section>
                    <section>
                        <h3>Admin Queries:</h3>
                        <form method="get" style="margin-bottom:6px">
                            <input type="hidden" name="action" value="admin_flights_tx_range" />
                            <span style="display: inline-block;">
                                <label>Texas City: <input name="city" placeholder="e.g. Dallas" /></label>
                                &emsp;<label>From: <input type="date" name="from" value="2024-09-01" /></label>
                                &emsp;<label>To: <input type="date" name="to" value="2024-10-31" /></label>
                            </span>
                            <button type="submit">Flights Departing TX (Sep-Oct)</button>
                        </form><br>
                        
                        <form method="get" style="margin-bottom:6px">
                            <input type="hidden" name="action" value="admin_hotels_tx_range" />
                            <span style="display: inline-block;">
                                <label>Texas City: <input name="city" placeholder="e.g. Dallas" /></label>
                                &emsp;<label>From: <input type="date" name="from" value="2024-09-01" /></label>
                                &emsp;<label>To: <input type="date" name="to" value="2024-10-31" /></label>
                            </span>
                            <button type="submit">Hotels in TX (Sep-Oct)</button>
                        </form><br>

                        <form method="get" style="margin-bottom:6px">
                            <input type="hidden" name="action" value="admin_top_hotels" />
                            <label>Top N: <input name="n" type="number" min="1" max="50" value="5" /></label>
                            <button type="submit">Most Expensive Booked Hotels</button>
                        </form><br>

                        <form method="get" style="margin-bottom:6px">
                            <input type="hidden" name="action" value="admin_flights_with_infant" />
                            <button type="submit">All Booked Flights with an Infant Passenger</button>
                        </form><br>

                        <form method="get" style="margin-bottom:6px">
                            <input type="hidden" name="action" value="admin_flights_infant_and_5children" />
                            <button type="submit">Flights with Infant and ≥5 children</button>
                        </form><br>

                        <form method="get" style="margin-bottom:6px">
                            <input type="hidden" name="action" value="admin_top_flights" />
                            <label>Top N: <input name="n" type="number" min="1" max="50" value="5" /></label>
                            <button type="submit">Most Expensive Booked Flights</button>
                        </form><br>

                        <form method="get" style="margin-bottom:6px">
                            <input type="hidden" name="action" value="admin_flights_tx_no_infant" />
                            <label>Texas City: <input name="city" placeholder="e.g. Dallas" /></label>
                            <button type="submit">Flights from TX with No Infant Passenger</button>
                        </form><br>

                        <form method="get" style="margin-bottom:6px">
                            <input type="hidden" name="action" value="admin_count_flights_arrive_ca_months" />
                            <label>California City: <input name="city" placeholder="e.g. Los Angeles" /></label>
                            <button type="submit">Count Flights Arriving CA (Sep/Oct 2024)</button>
                        </form>

                        <h3>Search Results:</h3>
                        <?php if ($adminMessage): ?>
                            <p><em><?php echo htmlspecialchars($adminMessage); ?></em></p>
                        <?php endif; ?>

                    <?php if (!empty($adminResults['data'])): ?>
                        <table border="1" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <?php foreach ($adminResults['columns'] as $header): ?>
                                        <th><?php echo htmlspecialchars($header); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($adminResults['data'] as $result): ?>
                                    <tr>
                                        <?php foreach ($result as $value): ?>
                                            <td><?php echo htmlspecialchars($value); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    </section>
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
