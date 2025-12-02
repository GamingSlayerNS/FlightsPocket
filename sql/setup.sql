CREATE DATABASE IF NOT EXISTS flightspocket;
USE flightspocket;

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
VALUES (
    '222-222-2222',
    '$2y$10$xOVnN9TwaKS38LYG3ULSTuIfDxVnNorWkfsbIASisDOmacDm0Vtke',
    'Admin',
    'Admin',
    '2000-01-01',
    null,
    'admin@example.com',
    1
);
