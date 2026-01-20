# Rent - Property Rental Platform

## Project Setup

### Folder Structure
- `public/` - Entry point (index.php)
- `src/` - Logic files (login_process.php, register_process.php)
- `config/` - Database configuration
- `views/` - HTML templates
- `assets/` - CSS, JS, Images

### Database Setup
1. Create database: `CREATE DATABASE rent_db;`
2. Create users table:
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Access the Project
- Navigate to: `http://localhost/Rent/public/`
- Home page will load

### Features
- User Registration
- User Login
- Basic Dashboard (to be implemented)
# RentBunny-Peer-to-Peer-Rental
