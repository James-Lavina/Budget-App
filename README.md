# Student Behavioral Budget System

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

## 📌 Project Overview
The **Student Behavioral Budget System** is an AI-driven financial tracking and decision-support web application designed to help students optimize their weekly allowances and mitigate impulsive spending habits. Moving away from standard retroactive accounting, this system introduces **Behavioral Budgeting Principles** by calculating absolute, live spending thresholds in real time.

This application served as the final capstone project fulfillment for an undergraduate Information Technology / Computer Science degree program.

---

## ⚡ Core Systems & Features

### 1. Dynamic Daily Safe-to-Spend Calculation Engine
* Automatically breaks down a student's remaining weekly allowance against the days left in their custom billing cycle.
* Recalculates constraints immediately following any logged expense transaction to provide an absolute spending ceiling for the day.

### 2. Intelligent AI Data Capture Pipeline
* **Receipt Scanning:** Integrated with the **OCR.space API** to perform high-speed Optical Character Recognition scanning on uploaded physical paper receipts.
* **Smart Information Extraction:** Raw textual OCR fragments are handled dynamically by the **Groq AI API** to automatically parse merchants, dates, line items, and transaction totals.

### 3. Granular Role-Based Access Control (RBAC)
* **Student Workspace:** Personalized interface featuring allowance parameters, historical transaction graphs, custom categorizations, and automated budgeting logs.
* **Administrative Security Panel:** Centralized command center allowing system monitors to audit system operational logs (`activity_logs`), analyze user enrollment counts, and maintain system health parameters.

### 4. Real-Time UI Reactivity
* Built entirely over custom full-page Livewire modules for smooth data synchronization without complete browser reloads.
* Employs server-driven validation loops (`updated()` hooks) to provide immediate input-field verification highlighting and error rendering for user credentials.

---

## 🛠️ Technical Architecture & Stack

* **Backend Framework:** Laravel 8.x (PHP 7.4+ / 8.0+)
* **Frontend Ecosystem:**
  * **Tailwind CSS v3** (Responsive utility-first UI framework)
  * **Laravel Livewire v2** (Reactive, server-driven dynamic interfaces)
* **Database Modeling:** MySQL / MariaDB (Relational data structure)
* **External Integrations:**
  * **OCR.space API** (Document text digitizing)
  * **Groq AI Engine** (LLM-based parsing and predictive budgeting analytics)

---
