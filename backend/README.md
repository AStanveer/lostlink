# LostLink Backend

PHP Slim 4 REST API with JWT authentication.

## Setup

```bash
composer install
cp .env.example .env   # fill in your DB credentials and JWT secret
```

Run the dev server:

```bash
php -S localhost:8080 -t public/
```

## Database

```bash
mysql -u root -p < ../database/schema.sql
mysql -u root -p lostlink < ../database/seed.sql  # optional test data
```

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/register` | No | Register new user |
| POST | `/login` | No | Login, returns JWT |
| GET | `/items` | Yes | List items (supports `?q=`, `?category=`, `?location=`, `?type=`) |
| POST | `/items` | Yes | Create item |
| PUT | `/items/{id}` | Yes | Update item (owner only) |
| DELETE | `/items/{id}` | Yes | Delete item (owner only) |
| POST | `/claims` | Yes | Submit claim |
| GET | `/matches` | Yes | Get smart matches |
| GET | `/dashboard/{userId}` | Yes | User's reports + claims |
