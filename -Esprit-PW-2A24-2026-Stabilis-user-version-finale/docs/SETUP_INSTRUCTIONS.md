# Ancien guide de setup

> Note: ce document est conserve pour historique. La structure actuelle du projet est decrite dans `docs/PROJECT_STRUCTURE.md`, et les scripts SQL/setup sont maintenant dans `database/`.

# Participation Flow Setup - Complete Guide

## ✅ What Was Fixed

1. **API Endpoint Fixed** (`app/api/create-participation.php`):
   - Fixed syntax error (incomplete `file_put_contents` statement)
   - Added proper error handling and logging
   - Added duplicate participation prevention
   - Now properly saves participation data to database

2. **Front-office Footer Fixed** (`font-office/footer.php`):
   - Removed duplicate `</body></html>` tags
   - Removed broken JavaScript that referenced non-existent elements

3. **Database Setup Script Created** (`database/setup-database.php`):
   - Creates database, tables (users, defis, participations)
   - Inserts test user (ID=1)
   - Inserts sample challenges if table is empty
   - Sets up foreign key relationships

## 🚀 How to Test the Complete Flow

### Step 1: Set Up the Database

1. Make sure XAMPP is running (Apache and MySQL)
2. Open your browser and go to:
   ```
   http://localhost/-Esprit-PW-2A24-2026-Stabilis-user-version-finale/database/setup-database.php
   ```
3. You should see a success message showing:
   - Database created
   - Tables created (users, defis, participations)
   - Test user inserted (ID: 1)
   - Sample challenges inserted

4. **IMPORTANT**: Delete the setup file after running for security:
   - Delete or protect `database/setup-database.php`

### Step 2: Test Front-Office Participation

1. Go to the front-office:
   ```
   http://localhost/-Esprit-PW-2A24-2026-Stabilis-user-version-finale/font-office/challenges.php
   ```

2. You should see a list of challenges (défis)

3. Click "Relever le défi" on any challenge

4. A modal will open with a participation form:
   - **Votre ID**: Enter `1` (the test user ID)
   - **Date de début**: Already set to today
   - **Progression**: Default 0%
   - **Statut**: Default "En cours"

5. Click "Commencer le défi" button

6. You should see a success message: "✓ Participation enregistrée avec succès!"

### Step 3: Verify in Back-Office

1. Go to the back-office:
   ```
   http://localhost/-Esprit-PW-2A24-2026-Stabilis-user-version-finale/back-office/
   ```

2. Click on "Participations" tab or go directly to:
   ```
   http://localhost/-Esprit-PW-2A24-2026-Stabilis-user-version-finale/back-office/?entity=participations
   ```

3. You should see your participation in the table with:
   - ID
   - Utilisateur (#1)
   - Défi (the challenge name)
   - Progression (0%)
   - Statut (En cours)
   - Date début (today)

## 🔍 Troubleshooting

### If participation doesn't save:

1. Check the API log file:
   ```
   back-office/api.log
   ```
   This file logs all API requests and errors.

2. Common issues:
   - **Database connection failed**: Check `app/config.php` credentials match your XAMPP MySQL
   - **User ID invalid**: Make sure users table exists and has ID=1
   - **Challenge ID invalid**: Make sure defis table exists and has data
   - **Duplicate participation**: User already joined this challenge

3. Check browser console (F12) for JavaScript errors

4. Verify the API endpoint is accessible:
   ```
   http://localhost/-Esprit-PW-2A24-2026-Stabilis-user-version-finale/app/api/create-participation.php
   ```
   (Should return JSON error about missing fields, not 404)

### Database Structure

The system uses three main tables:

1. **users** - Stores user information
   - id, nom, email, created_at

2. **defis** - Stores challenges
   - id, nom, type, objectif, recompense, created_at

3. **participations** - Stores user participation in challenges
   - id, id_utilisateur (FK to users), id_defi (FK to defis)
   - progression, statut, date_debut, date_fin, created_at

### Test Credentials

- **Test User ID**: 1
- **Test User Name**: Test User
- **Test User Email**: test@example.com

## 📊 Data Flow Diagram

```
Front-Office (font-office/challenges.php)
    ↓
User clicks "Relever le défi"
    ↓
Modal opens with participation form
    ↓
User fills form and clicks "Commencer le défi"
    ↓
JavaScript sends POST to app/api/create-participation.php
    ↓
API validates and saves to database (participations table)
    ↓
API returns JSON response (success/error)
    ↓
JavaScript shows success message and closes modal
    ↓
Page reloads to show updated state

Back-Office (back-office/)
    ↓
Reads from participations table
    ↓
Displays all participations with challenge names
    ↓
Admin can view, edit, delete participations
```

## 🛡️ Security Notes

- The API uses prepared statements to prevent SQL injection
- Input validation on both client and server side
- Foreign key constraints ensure data integrity
- **Delete setup-database.php after use** - it has elevated privileges

## 📝 Files Modified/Created

1. `app/api/create-participation.php` - Fixed and enhanced
2. `font-office/footer.php` - Fixed duplicate tags
3. `database/setup-database.php` - Database setup script
4. `SETUP_INSTRUCTIONS.md` - This file

## ✨ Features Working

- ✅ Front-office challenge listing
- ✅ Challenge modal with participation form
- ✅ Form submission to database
- ✅ Back-office participation listing
- ✅ Real-time data synchronization
- ✅ Error handling and validation
- ✅ Duplicate participation prevention
- ✅ Logging for debugging
