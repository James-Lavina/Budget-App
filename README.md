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
* **PHP** (v8.0, v8.1, or v8.2 with `zip`, `openssl`, `curl`, and `pdo_mysql` extensions enabled in `php.ini`)
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

### Step 4: Configure Database & API Credentials
1. Start your **MySQL** server (e.g., using the XAMPP Control Panel).
2. Create a new database in **phpMyAdmin** or MySQL CLI (e.g., `BudgetApp` or `budget_app_db`).
3. Open `.env` in a text editor and configure your database and external API parameters:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=BudgetApp
   DB_USERNAME=root
   DB_PASSWORD=

   # External API Integrations
   OCR_SPACE_API_KEY=your_ocr_space_api_key
   GROQ_API_KEY=your_groq_api_key
   ```

---

### Step 5: Run Database Migrations & Seeders
1. Build the database schema:
   ```bash
   php artisan migrate
   ```

2. Seed default admin user data (optional):
   ```bash
   php artisan db:seed --class=AdminUserSeeder
   ```

---

### Step 6: Launch the Application
Start Laravel's local development server:
```bash
php artisan serve
```

Open your browser and navigate to **`http://127.0.0.1:8000`**.
