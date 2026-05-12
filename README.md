# 🌿 Stabilis - Sustainable Nutrition & Smart Fitness

> **Fuel your body. Save our planet.**

## Overview

This project was developed as part of the **PW – 2nd Year Engineering Program** at
**Esprit School of Engineering – Tunisia** (Academic Year 2025–2026).

**Stabilis** is an innovative platform born from a critical observation: the difficulty of balancing physical performance, personal health, and environmental respect.

Our application empowers users to:

* **Transition** toward plant-based and non-processed diets.
* **Drastically reduce** food waste and individual carbon footprints.
* **Optimize** physical condition through scientific tracking.
* **Maintain motivation** via an advanced gamification system.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Contributors](#contributors)
- [Academic Context](#academic-context)
- [Getting Started](#getting-started)
- [Usage](#usage)
- [Commercial Poster](#commercial-poster)
- [Contribution](#contribution)
- [License](#license)
- [Acknowledgments](#acknowledgments)

## Features

### Key Modules & Innovative Features

The application is structured into 5 core modules, each powered by smart technology:

### 👤 User Management

* **Advanced Security:** Biometric access using FaceID, Multi-Factor Authentication, and reCAPTCHA integration.
* **Status Lifecycle:** Post-registration email verification with active/inactive status management.
* **Password Recovery System:** Secure "Forgot Password" workflow using automated reset tokens sent by email.
* **Data Export:** Generate user profiles and progress reports in PDF format.

### 🛒 Green E-Commerce

* **AI Personalization:** Smart recommendation engine based on user behavior and preferences.
* **Secure Payments:** Full integration of the Stripe API in testing mode.
* **AI Marketing:** Automated product description generation and behavior-based AI promo codes.
* **Inventory Alerts:** Automated stock notifications and pre-order systems.

### 🥗 AI Meals & Recipes Anti-Waste

* **Smart Fridge:** Computer Vision analysis of fridge photos for instant inventory.
* **Zero-Waste Cooking:** AI recipe generator that prioritizes available leftovers and expiring ingredients.
* **Eco-Validation:** AI-driven data validation and automatic recipe self-improvement.

### 🏆 Gamified Challenges

* **Gemini-Generated Challenges:** Custom challenges created using Gemini AI with adjustable topics and difficulty.
* **AI Proof Validation:** Image analysis of user-submitted proofs with approval/rejection and AI confidence scores.
* **AI Narrative:** Weekly automated success stories and summaries to boost community engagement.
* **Leaderboards:** Dynamic ranking system with animated podiums and real-time point calculation.

### 🏋️ Smart Workout Log

* **Scientific Logic:** MET-based calorie calculation for precise effort tracking.
* **Virtual Coach:** Automated workout programs and movement tutorials generated using AI.
* **Performance Log:** Comprehensive session tracking including heart rate, intensity, frequency, and personal notes.

## Tech Stack

* **Languages:** PHP, HTML/CSS, JavaScript.
* **AI & Machine Learning:**
  * **Face-api.js:** Browser-based Artificial Intelligence for Face ID and facial recognition.
  * **Gemini AI:** Advanced LLM for challenge generation and evidence analysis.
* **Database:** MySQL.
* **APIs & Tools:** Stripe API, FPDF, PHPMailer.

### Frontend

- HTML
- CSS
- JavaScript

### Backend

- PHP
- MySQL

## Architecture

The project follows the **MVC architecture** to separate the application logic, user interface, and data management.

- **Model:** Handles database interactions and business logic.
- **View:** Displays the user interface using HTML, CSS, and JavaScript.
- **Controller:** Processes user requests and connects the models with the views.

This structure helps keep the code organized, maintainable, and easier to extend.

## Contributors

- Abdelmajid Bejaoui
- Zaineb Hidoussi
- Youssef Zaghouan
- Dhiaeddine Lamouchi
- Malek Ben Mohammed

## Academic Context

Developed at **Esprit School of Engineering – Tunisia**  
PW – 2nd Year | Academic Year 2025–2026

## Getting Started

### Installation

1. Clone the repository:

```bash
git clone https://github.com/AbdelmajidBejaoui/-Esprit-PW-2A24-2026-Stabilis.git
cd -Esprit-PW-2A24-2026-Stabilis
```

2. Move the project folder to your local server directory:

- For XAMPP: place it inside `htdocs`
- For WAMP: place it inside `www`

3. Start Apache and MySQL from XAMPP or WAMP.

4. Open phpMyAdmin and create a new database named:

```bash
stabilis
```

5. Import the provided `.sql` database file.

6. Configure the database connection file with your local settings:

```php
$host = "localhost";
$dbname = "stabilis";
$username = "root";
$password = "";
```

7. Open the application in your browser:

```bash
http://localhost/Stabilis
```

## Usage

After installing the project, users can create an account, verify their email, explore the platform modules, participate in challenges, track workouts, generate AI-based content, and use the Green E-Commerce system.

### User Side

- Register and verify an account by email.
- Browse healthy and eco-friendly products.
- Use AI-generated recommendations and promo codes.
- Participate in gamified challenges.
- Submit proof images for AI validation.
- Track workout sessions and fitness progress.
- Generate recipes based on available ingredients.

### Admin Side

- Manage users, products, orders, challenges, recipes, and workout content.
- Monitor statistics and platform activity.
- Generate PDF reports.
- Send automated emails and stock alerts.
- Manage product promotions and pre-orders.

## Commercial Poster

Here is our official commercial poster for **Stabilis**:

<img width="1054" height="1492" alt="Stabilis Commercial Poster" src="https://github.com/user-attachments/assets/9147ea44-02a9-4d9d-bef9-d54feae107cb" />

## Contribution

We thank everyone who contributed to this project.

### Contributors and Roles

- **Abdelmajid Bejaoui:** Green E-Commerce module, AI product descriptions, promo codes, stock alerts, pre-order system, and Stripe integration.
- **Zaineb Hidoussi:** User Management module, authentication, security, email verification, and password recovery.
- **Youssef Zaghouan:** AI Meals & Recipes module, smart fridge, recipe generation, and anti-waste features.
- **Dhiaeddine Lamouchi:** Gamified Challenges module, AI challenge generation, proof validation, and leaderboard system.
- **Malek Ben Mohammed:** Smart Workout Log module, workout tracking, MET-based calories, and virtual coach features.

### How to Contribute

1. Fork the repository.

2. Create a new branch:

```bash
git checkout -b feature/your-feature-name
```

3. Add your changes:

```bash
git add .
```

4. Commit your changes:

```bash
git commit -m "Add new feature"
```

5. Push your branch:

```bash
git push origin feature/your-feature-name
```

6. Open a pull request.

## License

This project was developed for academic purposes as part of the **PW – 2nd Year Engineering Program** at **Esprit School of Engineering**.

All rights reserved © 2025–2026 Stabilis Team.

## Acknowledgments

We would like to thank our professors and supervisors at Esprit School of Engineering for their guidance and support throughout the development of this project.
