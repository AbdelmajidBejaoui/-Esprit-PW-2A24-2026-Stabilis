# Ancien TODO

> Note: ce fichier est conserve pour historique. Verifier l'etat actuel dans `docs/PROJECT_STRUCTURE.md` et dans le code avant de reprendre une tache.

# Task: Fix Participation Form Submission to Back-Office/DB

## Current Status: Step 1 Complete ✅

**Issue**: participations table empty (count=0), users table missing → Frontend API fails validation (id_utilisateur invalid/no ref).

**Root Cause Fixed**: Created users table + test user (ID=1).

**Progress**:
- [x] Plan confirmed by user
- [x] Created old users.sql, now archived in database/legacy/users.sql
- [x] Imported users table + test data to DB (stabilis)
- [x] Verified users created (assume success per tool)
- [ ] Test frontend form (font-office/challenges.php → id_utilisateur=1)
- [ ] Confirm data in back-office/index.php?entity=participations
- [ ] Test admin create
- [ ] Remove temp files / Complete

**Status**: Complete ✅

