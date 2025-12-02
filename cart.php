<?php
require_once 'php/util/authentication.php';
requireAuth();
$user = $GLOBALS['user'];
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Cart - Flights Pocket</title>
        <link rel="stylesheet" href="src/mystyle.css" />
        <script src="hooks/app.js"></script>
        <script src="hooks/cart.js"></script>
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
            <div>Hello <?php echo $user['FirstName'] . ' ' . $user['LastName'] ?>!</div>
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
        </nav>
        <main>
            <aside class="open">
                <div>
                    <h2>Options</h2>
                    <label for="font-size">Font Size:</label>
                    <input type="range" id="font-size" min="10" max="30" value="16" />
                    <label for="bg-color">Background Color:</label>
                    <input type="color" id="bg-color" value="#ffffff" />
                </div>
            </aside>
            <div class="container">
                <h2>Your Cart</h2>
                <div id="cart-content"></div>
            </div>
        </main>
        <footer>
            <p>
                <strong>CS6314.001</strong><br />Developed by <strong>Danny Bao ( DXB180034 )</strong>,
                <strong>Samuel Preston ( SWP210000 )</strong>, <strong>Naxel Santiago Rivera ( NAS180011 )</strong
                ><br />&copy; 2025 Flights Pocket
            </p>
        </footer>
    </body>
</html>
