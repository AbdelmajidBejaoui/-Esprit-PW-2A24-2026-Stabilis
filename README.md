# 🏋️ FitTrack - AI-Powered Fitness Tracking System

A modern fitness tracking application with AI-powered workout generation using Google Gemini API.

## 🚀 Features

### Frontend (User Interface)
- **AI Workout Generator** - Generate personalized workouts using natural language prompts
- **Workout Catalogue** - Browse AI-recommended workouts by goal and level
- **My Program** - Save and manage your workout collection
- **Session Tracking** - Log completed workouts with calories burned
- **User Profile** - Track progress, BMI, and performance metrics

### Backend (Admin Panel)
- **Dashboard** - Overview of system statistics and AI generations
- **User Management** - Manage registered users
- **Workout Management** - View user-created workouts
- **AI History** - Complete history of AI-generated sessions
- **Session Tracking** - Monitor all completed workout sessions

## 🛠️ Technology Stack

- **Backend**: PHP 8.x
- **Database**: MySQL
- **AI**: Google Gemini API (gemini-2.5-flash)
- **Frontend**: Bootstrap 4, AdminLTE 3
- **Architecture**: Clean layered architecture (Repository → Service → Controller)

## 📁 Project Structure

```
entrainements_project/
├── Controller/          # Controllers (orchestrate services)
│   ├── AIGeneratorC.php
│   ├── EntrainementC.php
│   ├── ProgrammeC.php
│   ├── SeanceC.php
│   └── UtilisateurC.php
├── Core/               # Core infrastructure
│   ├── Database.php
│   └── Repository.php
├── Model/              # Data models
│   ├── Entrainement.php
│   ├── Seance.php
│   └── Utilisateur.php
├── Repository/         # Data access layer
│   ├── EntrainementRepository.php
│   └── GeneratedSessionRepository.php
├── Service/            # Business logic
│   ├── AI/
│   │   ├── AIWorkoutGenerator.php
│   │   ├── GeminiClient.php
│   │   └── WorkoutPromptBuilder.php
│   ├── CalorieService.php
│   ├── EntityHelper.php
│   ├── PerformanceTracker.php
│   ├── ProgramAdaptationService.php
│   └── WorkoutGeneratorService.php
├── View/
│   ├── BackOffice/     # Admin panel
│   └── FrontOffice/    # User interface
├── public/             # Static assets
├── config.php          # Configuration
└── database.clean.sql  # Database schema
```

## 🗄️ Database Schema

**6 Essential Tables:**

1. **utilisateur** - User accounts
2. **entrainements** - Saved AI workouts
3. **seances_completees** - Completed workout sessions
4. **programme_utilisateur** - User's workout programs
5. **etapes_exercice** - Workout tutorial steps
6. **generated_sessions** - AI generation history

## ⚙️ Installation

### 1. Prerequisites
- XAMPP (Apache + MySQL + PHP 8.x)
- Google Gemini API key (free at https://aistudio.google.com/app/apikey)

### 2. Setup Database
```sql
-- Import the database
mysql -u root < database.clean.sql
```

### 3. Configure API
```php
// config.php
define('GEMINI_API_KEY', 'your_api_key_here');
```

### 4. Access Application
- **Frontend**: http://localhost/entrainements_project/View/FrontOffice/catalogue.php
- **Backend**: http://localhost/entrainements_project/View/BackOffice/dashboard.php

## 🔑 Default Credentials

**Test User:**
- Email: test@fitness.com
- Password: password

## 🤖 AI Workout Generation

The system uses Google Gemini AI to generate personalized workouts based on:
- **Goal**: Weight loss, muscle gain, or endurance
- **Level**: Beginner, intermediate, or advanced
- **Custom Prompt**: Natural language description of what you want

Example prompts:
- "I want to work legs and abs, 8 exercises, no burpees"
- "Focus on upper body strength"
- "Cardio only, 30 minutes"

## 📊 Architecture

### Clean Layered Architecture

```
┌─────────────────────────────────────┐
│  Controllers (5)                    │
│  └─ Orchestrate services            │
├─────────────────────────────────────┤
│  Services (6)                       │
│  ├─ AI/GeminiClient                │
│  ├─ AI/AIWorkoutGenerator          │
│  ├─ CalorieService                 │
│  └─ Others...                       │
├─────────────────────────────────────┤
│  Repositories (2)                   │
│  └─ Data persistence                │
├─────────────────────────────────────┤
│  Core (2)                           │
│  ├─ Database                        │
│  └─ Repository (base)               │
├─────────────────────────────────────┤
│  Database (6 tables)                │
│  └─ Minimal, AI-first               │
└─────────────────────────────────────┘
```

### Key Design Principles

1. **AI-First**: Everything is generated dynamically, no static data
2. **Clean Architecture**: Clear separation of concerns
3. **Single Responsibility**: Each class has one job
4. **DRY**: No code duplication
5. **MET Formula**: Scientific calorie calculations

## 🔥 Calorie Calculation

Uses the MET (Metabolic Equivalent of Task) formula:

```
Calories = MET × weight(kg) × duration(hours)
```

Example MET values:
- Burpees: 10.3
- Squats: 6.0
- Push-ups: 4.0
- Plank: 3.5

## 📈 Performance Tracking

The system tracks:
- Total sessions completed
- Total calories burned
- Average calories per session
- Workout intensity
- User progress over time

## 🔒 Security

- Password hashing with bcrypt
- Session-based authentication
- SQL injection protection (prepared statements)
- XSS protection (htmlspecialchars)

## 🚀 Workflow

1. **User generates AI workout** → Saved to `generated_sessions`
2. **User clicks "Save to Program"** → Saved to `entrainements` + `programme_utilisateur`
3. **User completes workout** → Logged to `seances_completees`
4. **Admin views statistics** → Dashboard shows all data

## 📝 API Configuration

The system uses Google Gemini API (free tier):
- Model: `gemini-2.5-flash`
- Free quota: Generous for personal projects
- Get your key: https://aistudio.google.com/app/apikey

## 🐛 Troubleshooting

### AI Generation Not Working
- Check API key in `config.php`
- Verify internet connection
- Check API quota

### Database Errors
- Ensure MySQL is running
- Check database name: `gestion_fitness`
- Verify table names match schema

### Page Not Found
- Check Apache is running
- Verify file paths
- Check .htaccess if using Apache

## 📄 License

This project is for educational purposes.

## 👨‍💻 Development

Built with clean architecture principles and modern PHP practices.

**Key Features:**
- ✅ 100% AI-generated workouts
- ✅ Clean, maintainable codebase
- ✅ Minimal database (6 tables)
- ✅ Professional architecture
- ✅ No static data bloat

---

**Made with ❤️ and AI**
