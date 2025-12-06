<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Register</title>
        <link rel="stylesheet" href="src/mystyle.css" />
        <script src="hooks/app.js"></script>
        <script src="hooks/register.js" defer></script>
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
                <h2>Register</h2>
                <form id="register-form">
                  <div>
                    <label for="first-name">First Name:</label>
                    <input type="text" id="first-name" name="first-name" required />
                  </div>
                  <div>
                    <label for="last-name">Last Name:</label>
                    <input type="text" id="last-name" name="last-name" required />
                  </div>
                  <div>
                    <label for="phone-number">Phone Number<br/> (XXX-XXX-XXXX):</label>
                    <input type="tel" id="phone-number" name="phone-number" required />
                  </div>
                  <div>
                    <label for="date-of-birth">Date of Birth<br/> (MM/DD/YYYY):</label>
                    <input type="text" id="date-of-birth" name="date-of-birth" placeholder="MM/DD/YYYY" required />
                  </div>
                  <div class="flex-row">
                    <label>Gender:</label>
                    <div class="radio">
                        <div>
                            <input type="radio" id="male" name="gender" value="Male" />
                            <label for="male">Male</label>
                        </div>
                        <div>
                            <input type="radio" id="female" name="gender" value="Female" />
                            <label for="female">Female</label>
                        </div>
                    </div>
                </div>
                  <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required />
                  </div>
                  <div>
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required />
                  </div>
                  <div>
                    <label for="confirm-password">Confirm Password:</label>
                    <input type="password" id="confirm-password" name="confirm-password" required />
                  </div>
                  <div>
                    <a href="/login.php">Log in to an account</a>
                  </div>
                  <button type="submit">Submit</button>
                </form>
                <div id="register-errors" role="alert" aria-live="polite"></div>
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
