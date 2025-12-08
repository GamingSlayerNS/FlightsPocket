CREATE DATABASE IF NOT EXISTS flightspocket;
USE flightspocket;

-- Drop existing tables to apply changes
DROP TABLE IF EXISTS Tickets;
DROP TABLE IF EXISTS FlightBookings;
DROP TABLE IF EXISTS Passengers;
DROP TABLE IF EXISTS Flights;
DROP TABLE IF EXISTS Sessions;
DROP TABLE IF EXISTS Guests;
DROP TABLE IF EXISTS HotelBookings;
DROP TABLE IF EXISTS Hotels;
DROP TABLE IF EXISTS Users;


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
    FlightID VARCHAR(10) PRIMARY KEY,
    Origin VARCHAR(100) NOT NULL,
    Destination VARCHAR(100) NOT NULL,
    DepartureDate DATE NOT NULL,
    ArrivalDate DATE NOT NULL,
    DepartureTime TIME NOT NULL,
    ArrivalTime TIME NOT NULL,
    AvailableSeats INT NOT NULL CHECK (AvailableSeats >= 0),
    Price DECIMAL(10, 2) NOT NULL CHECK (Price >= 0)
);

-- Passenger Table
CREATE TABLE IF NOT EXISTS Passengers (
    SSN VARCHAR(100) PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    DateOfBirth DATE NOT NULL,
    Category ENUM('Adult', 'Child', 'Infant') NOT NULL
);

-- Flight-booking Table
CREATE TABLE IF NOT EXISTS FlightBookings (
    FlightBookingID VARCHAR(10) PRIMARY KEY,
    FlightID VARCHAR(10) NOT NULL,
    TotalPrice DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (FlightID) REFERENCES Flights(FlightID)
);

-- Tickets Table
CREATE TABLE IF NOT EXISTS Tickets (
    TicketID INT AUTO_INCREMENT PRIMARY KEY,
    FlightBookingID VARCHAR(10) NOT NULL,
    SSN VARCHAR(11) NOT NULL,
    Price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (FlightBookingID) REFERENCES FlightBookings(FlightBookingID),
    FOREIGN KEY (SSN) REFERENCES Passengers(SSN)
);

-- Hotels Table
CREATE TABLE IF NOT EXISTS Hotels (
    HotelID VARCHAR(10) PRIMARY KEY,
    HotelName VARCHAR(255) NOT NULL,
    City VARCHAR(100) NOT NULL,
    PricePerNight DECIMAL(10, 2) NOT NULL CHECK (PricePerNight >= 0)
);

-- Hotel-booking Table
CREATE TABLE IF NOT EXISTS HotelBookings (
    HotelBookingID VARCHAR(10) PRIMARY KEY,
    HotelID VARCHAR(10) NOT NULL,
    CheckInDate DATE NOT NULL,
    CheckOutDate DATE NOT NULL,
    NumberOfRooms INT NOT NULL CHECK (NumberOfRooms > 0),
    PricePerNight DECIMAL(10, 2) NOT NULL CHECK (PricePerNight >= 0),
    TotalPrice DECIMAL(10, 2) NOT NULL CHECK (TotalPrice >= 0),
    FOREIGN KEY (HotelID) REFERENCES Hotels(HotelID)
);

-- Guests Table
CREATE TABLE IF NOT EXISTS Guests (
    SSN VARCHAR(100) PRIMARY KEY,
    HotelBookingID VARCHAR(10) NOT NULL,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    DateOfBirth DATE NOT NULL,
    Category ENUM('Adult', 'Child', 'Infant') NOT NULL,
    FOREIGN KEY (HotelBookingID) REFERENCES HotelBookings(HotelBookingID)
);