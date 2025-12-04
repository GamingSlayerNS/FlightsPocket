CREATE DATABASE IF NOT EXISTS flightspocket;
USE flightspocket;

-- Users Table
CREATE TABLE IF NOT EXISTS Users (
    PhoneNumber VARCHAR(20) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    DateOfBirth DATE NOT NULL,
    Gender VARCHAR(10) NULL,
    Email VARCHAR(255) NOT NULL,
    Admin BIT DEFAULT 0
);

INSERT INTO Users
(PhoneNumber, Password, FirstName, LastName, DateOfBirth, Gender, Email, Admin)
SELECT 
    '222-222-2222',
    '$2y$10$xOVnN9TwaKS38LYG3ULSTuIfDxVnNorWkfsbIASisDOmacDm0Vtke', -- Password: 1234567890
    'Admin',
    'Admin',
    '2000-01-01',
    null,
    'admin@example.com',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM Users WHERE PhoneNumber = '222-222-2222'
);

CREATE TABLE IF NOT EXISTS Sessions (
    User VARCHAR(20) UNIQUE NOT NULL,
    Token VARCHAR(32) NOT NULL,
    Expiration DATETIME NOT NULL,
    FOREIGN KEY (User) REFERENCES Users(PhoneNumber)
);

-- Flights Table
CREATE TABLE IF NOT EXISTS Flights (
    FlightID INT AUTO_INCREMENT PRIMARY KEY,
    Origin VARCHAR(100) NOT NULL,
    Destination VARCHAR(100) NOT NULL,
    DepartureDate DATE NOT NULL,
    ArrivalDate DATE NOT NULL,
    DepartureTime TIME NOT NULL,
    ArrivalTime TIME NOT NULL,
    AvailableSeats INT NOT NULL,
    Price DECIMAL(10, 2) NOT NULL
);

-- Passenger Table
CREATE TABLE IF NOT EXISTS Passengers (
    SSN CHAR(9) PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    DateOfBirth DATE NOT NULL,
    Category ENUM('Adult', 'Child', 'Infant') NOT NULL
);

-- Flight-booking Table
CREATE TABLE IF NOT EXISTS FlightBookings (
    FlightBookingID INT AUTO_INCREMENT PRIMARY KEY,
    FlightID INT NOT NULL,
    TotalPrice DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (FlightID) REFERENCES Flights(FlightID)
);

-- Tickets Table
CREATE TABLE IF NOT EXISTS Tickets (
    TicketID INT AUTO_INCREMENT PRIMARY KEY,
    FlightBookingID INT NOT NULL,
    SSN CHAR(9) NOT NULL,
    Price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (FlightBookingID) REFERENCES FlightBookings(FlightBookingID),
    FOREIGN KEY (SSN) REFERENCES Passengers(SSN)
);