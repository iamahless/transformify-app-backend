# Appointment App Backend

This is a Symfony-based backend application for managing appointments and participants. It provides RESTful APIs to create, retrieve, update, and delete appointments and participants, with conflict detection and validation.

## Features

- Manage appointments with participants
- Prevent overlapping appointments for participants
- CRUD operations for appointments and participants
- JSON API responses
- Validation and error handling
- Unit and integration tests with PHPUnit

## Requirements

- PHP 8.1 or higher
- Composer
- Symfony CLI (optional, for local server)
- A supported database (e.g., MySQL, PostgreSQL, SQLite)

## Getting Started

### 1. Install Dependencies

```sh
composer install
```

### 2. Configure Environment

Copy the example environment file and adjust settings as needed:

```sh
cp .env .env.local
```

Edit `.env.local` to set your database connection and other environment variables.

### 3. Set Up the Database

Create the database and run migrations:

```sh
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 4. Run the Application Locally

Start the Symfony local server:

```sh
symfony server:start
```

Or use the built-in PHP server:

```sh
php -S localhost:8000 -t public
```

The API will be available at `http://localhost:8000`.

## API Endpoints

- `GET /appointments` — List all appointments
- `POST /appointments` — Create a new appointment
- `GET /appointments/{id}` — Get appointment details
- `PUT|PATCH /appointments/{id}` — Update an appointment
- `DELETE /appointments/{id}` — Delete an appointment

- `GET /participants` — List all participants
- `POST /participants` — Create a new participant
- `GET /participants/{id}` — Get participant details
- `DELETE /participants/{id}` — Delete a participant

## Postman Documentation

You can find the Postman API documentation here: 

## Running Tests

The project uses PHPUnit for testing.

### 1. Configure the Test Environment

Ensure your `.env.test` is configured for your test database.

### 2. Run the Test Suite

```sh
php bin/phpunit
```

Or, if you have Symfony CLI:

```sh
symfony php bin/phpunit
```

Test results will be shown in the console.

## License

This project is licensed under the MIT License.
