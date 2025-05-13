# Redmine Progress Tracker

A Laravel-based application for tracking and visualizing task completion rates from Redmine.

## Features

- Daily and monthly task completion statistics
- Graphical visualization using Chart.js
- Filter by date range and project
- Summary of completion rates

## Requirements

- Docker and Docker Compose

## Installation with Docker

1. Clone the repository:
```bash
git clone https://github.com/yktk435/Devin.git
cd Devin/redmine-progress-tracker
```

2. Start the Docker containers:
```bash
docker-compose up -d
```

3. The application will be available at http://localhost:8000

## Configuration

To connect to your Redmine instance, update the following environment variables in the `.env` file:

```
REDMINE_API_URL=http://your-redmine-instance
REDMINE_API_KEY=your-api-key
```

## Development

### Running Commands in the Container

To run Laravel commands inside the container:

```bash
docker-compose exec app php artisan <command>
```

### Accessing the Database

The MySQL database is accessible at:
- Host: localhost
- Port: 3306
- Username: root
- Password: root
- Database: laravel

## Manual Installation (without Docker)

If you prefer to run the application without Docker:

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env` and configure your environment
4. Generate application key: `php artisan key:generate`
5. Start the server: `php artisan serve`

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
