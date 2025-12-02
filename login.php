<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Login</title>
        <link rel="stylesheet" href="src/mystyle.css" />
        <script src="hooks/app.js"></script>
        <script src="hooks/login.js" defer></script>
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
                <h2>Login</h2>
                <form id="login-form">
                    <div>
                        <label for="phone-number">Phone Number<br/> (XXX-XXX-XXXX):</label>
                        <input type="tel" id="phone-number" name="phone-number" required />
                    </div>
                    <div>
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required />
                    </div>
                    <button type="submit">Login</button>
                </form>
                <div id="login-errors" role="alert" aria-live="polite"></div>
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
