# Student Behavioral Budget System

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

## 📌 Project Overview
The **Student Behavioral Budget System** is an AI-driven financial tracking and decision-support web application designed to help students optimize their weekly allowances and mitigate impulsive spending habits. Moving away from standard retroactive accounting, this system introduces **Behavioral Budgeting Principles** by calculating absolute, live spending thresholds in real time.

This application served as the final capstone project fulfillment for an undergraduate Information Technology / Computer Science degree program.

---

## 🛠️ Technical Architecture & Stack

* **Backend Framework:** Laravel 8.x (PHP 7.4+ / 8.0+ / 8.1+)
* **Frontend Ecosystem:**
  * **Tailwind CSS v3** (Responsive utility-first UI framework)
  * **Laravel Livewire v2** (Reactive, server-driven dynamic interfaces)
* **Database Modeling:** MySQL / MariaDB (Relational data structure)
* **External Integrations:**
  * **OCR.space API** (Document text digitizing)
  * **Groq AI Engine** (LLM-based parsing and predictive budgeting analytics)

---

## 🚀 Local Installation & Setup Guide

Follow these steps to clone, configure, and run the project locally on Windows / macOS / Linux environments.

### Prerequisites
Ensure you have the following installed on your machine:
* **Git**
* **PHP** (v8.0, v8.1, or v8.2 with `zip`, `openssl`, `curl`, `gd`, and `pdo_mysql` extensions enabled in `php.ini`)
* **Composer** (PHP Package Manager)
* **Node.js** (v18+) & **NPM**
* **MySQL / MariaDB** (e.g., via XAMPP)

---

### Step 1: Clone the Repository
Open your terminal or command prompt and clone the project:
```bash
git clone https://github.com/James-Lavina/Budget-App.git
cd Budget-App
```

---

### Step 2: Install PHP & Node Dependencies
Install the backend and frontend package dependencies:
```bash
# Install PHP dependencies
# (Use --ignore-platform-reqs if using PHP 8.2 or newer)
composer install --ignore-platform-reqs

# Install Node modules and build assets
npm install
npm run dev
```

---

### Step 3: Configure Environment File
1. Copy the example environment file to create your local `.env`:
   ```bash
   cp .env.example .env
   ```
   *(On Windows Command Prompt, run: `copy .env.example .env`)*

2. Generate the application encryption key:
   ```bash
   php artisan key:generate
   ```

---

### Step 4: Configure Database, Admin Account & API Credentials
1. Start your **MySQL** server (e.g., using the XAMPP Control Panel).
2. Create a new, **empty** database in **phpMyAdmin** or the MySQL CLI (e.g., `BudgetApp` or `budget_app_db`).
3. Open `.env` in a text editor and fill in the following:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=BudgetApp
   DB_USERNAME=root
   DB_PASSWORD=

   # First super admin account (created by the seeder in Step 5).
   # Choose your own email and a password of at least 8 characters.
   ADMIN_NAME="Super Admin"
   ADMIN_EMAIL=you@example.com
   ADMIN_PASSWORD=choose-a-strong-password

   # External API Integrations
   OCR_SPACE_API_KEY=your_ocr_space_api_key
   GROQ_API_KEY=your_groq_api_key
   ```

> **Why no default admin login?** No username or password is stored in the repository. A published default password would let anyone log in to every installation, so you choose your own in `.env`.
>
> **About the Groq key:** `GROQ_API_KEY` in `.env` is only a fallback. Once the app is running, the key and model settings can be managed in **Admin → OCR & AI Settings**, where the key is stored encrypted in the database. Keys are never included in the repository, so every installation needs its own.

---

### Step 5: Run Database Migrations & Seeders
Build the schema and load the starter data in one command:
```bash
php artisan migrate --seed
```

This creates:

* **The expense categories** (with icons, colors, auto-detect keywords and the catch-all "other" fallback): Academics & Supplies, Entertainment & Leisure, Food & Dining, other, Personal Care & Health, Savings, Transportation, and Utilities & Internet.
* **One super admin account**, using `ADMIN_EMAIL` and `ADMIN_PASSWORD` from your `.env`.

Good to know:

* If `ADMIN_EMAIL` or `ADMIN_PASSWORD` is empty, the admin step is skipped with a warning. Fill them in and run `php artisan db:seed --class=SuperAdminSeeder`.
* Seeding is safe to repeat. Existing categories and accounts are left as they are, so later admin edits are never overwritten.
* Do **not** use `php artisan migrate:fresh` on a database you care about. It deletes every table.
* Other settings (app name, brand color, risk detection rules, AI settings) create themselves with defaults the first time they are opened.

---

### Step 6: Link Public Storage
Uploaded logos are served from the public disk, so create the storage link once:
```bash
php artisan storage:link
```
*(On Windows, run the terminal as Administrator if symbolic links are refused.)*

---

### Step 7: Launch the Application
Start Laravel's local development server:
```bash
php artisan serve
```

Open your browser and navigate to **`http://127.0.0.1:8000`**.

* **Admin:** log in at `/login` with the `ADMIN_EMAIL` / `ADMIN_PASSWORD` you set. You will land on the admin dashboard.
* **Students:** register at `/register`. New students complete a short budget setup on first login.
* After your first login, open **Admin → OCR & AI Settings** to confirm your Groq key works (use the **Test** buttons).

---

### Step 8 (Optional): Scheduled Alerts
Daily "no expense logged" alerts and log cleanup run through Laravel's scheduler. During development, leave this running in a second terminal:
```bash
php artisan schedule:work
```
On a server, add this cron entry instead:
```
* * * * * cd /path/to/Budget-App && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 Running the Test Suite

The tests rebuild the database tables before running, so they **must use a separate, empty database**, never the one you use for development.

1. Create an empty database such as `BudgetApp_test`.
2. Point the tests at it, either in `phpunit.xml`:
   ```xml
   <server name="DB_DATABASE" value="BudgetApp_test"/>
   ```
   or in a `.env.testing` file containing `DB_DATABASE=BudgetApp_test`.
3. Run:
   ```bash
   php artisan test
   ```

---

## 🩺 Troubleshooting

| Problem | Fix |
|---|---|
| `Class "Database\Seeders\ExpenseCategorySeeder" not found` | Run `composer dump-autoload`. |
| "Skipped super admin" message while seeding | Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env`, run `php artisan config:clear`, then `php artisan db:seed --class=SuperAdminSeeder`. |
| Changed `.env` but nothing happens | Run `php artisan config:clear`. |
| Logo or uploaded images do not show | Run `php artisan storage:link`. |
| Receipt scanning fails | Check **Admin → OCR & AI Settings** (Test buttons) and `OCR_SPACE_API_KEY` in `.env`. |
| Page is unstyled | Run `npm install && npm run dev`. |