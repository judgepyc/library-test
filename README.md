# Library API - Qualification Work

REST API for a mobile book library application developed on Laravel 10/11.

## Features implemented
- **Authentication**: Registration, Login, Logout (Sanctum Tokens).
- **Users**: List users, grant access to private libraries (Many-to-Many).
- **Books CRUD**:
  - Create book (via text or .txt file upload).
  - Read (view own books or books of allowed users).
  - Update & Soft Delete (with Restore capability).
- **Integration**: Search and save books from **Google Books API**.

## Requirements
- PHP 8.2+
- MySQL 8
- Composer

## Installation

1. **Clone the repository**
   ```bash
   git clone <your-repo-link>
   cd <project-folder>
   ```
2. **Install dependencies**
    ```bash
    composer install
    ```
3. **Environment Setup**
    ```bash
    cp .env.examle .env
    php artisan key:generate
    ```
    Configure your DB settings in .env file
4. **Migrations**
    ```bash
    php artisan migrate
    ```
5. **Run**
    ```bash
    php artisan serve
    ```
6. **APIs**
    Repository containes a Bruno collection(library.json). Import it into Bruno and make a tests.