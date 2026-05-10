# Structure du projet Stabilis

Ce projet est une application PHP/MySQL pour une plateforme de defis nutrition, bien-etre et habitudes durables.

## Entrees principales

- `index.php` redirige vers le front-office.
- `font-office/` contient les pages visibles par les utilisateurs.
- `back-office/index.php` est le routeur principal du back-office.
- `app/api/` contient les endpoints AJAX/JSON utilises par le front-office et le back-office.

## Coeur MVC back-office

- `app/controllers/` contient les controleurs back-office.
- `app/models/` contient les modeles et les requetes metier.
- `app/views/` contient les vues HTML/PHP du back-office.
- `app/core/` contient les services partages: base de donnees, Gemini, garde admin, validation.

Le back-office suit le flux:

```text
back-office/index.php
    -> app/controllers/*
    -> app/models/*
    -> app/views/*
```

## Front-office

Le dossier actif est `font-office/`.

Le front-office reste organise en pages PHP simples pour conserver les URLs existantes:

- `font-office/index.php`
- `font-office/challenges.php`
- `font-office/my-challenges.php`
- `font-office/weekly-recap.html`

La connexion DB du front-office passe par `font-office/config.php`, qui reutilise maintenant `app/core/Database.php`.

## APIs

Les APIs sont separees dans `app/api/`.
Elles retournent du JSON et utilisent les modeles/services du dossier `app/`.

Exemples:

- `app/api/create-participation.php`
- `app/api/upload-proof.php`
- `app/api/weekly-recap.php`
- `app/api/generate-challenges.php`
- `app/api/generate-weekly-story.php`

## Base de donnees et scripts

Les scripts SQL/setup ne sont pas du runtime MVC.
Ils sont ranges dans:

- `database/setup-database.php`
- `database/participations.sql`
- `database/legacy/`

Le dossier `database/legacy/` contient d'anciens fichiers conserves pour reference uniquement.

## Documents

- `docs/PROJECT_STRUCTURE.md` explique cette organisation.
- `docs/SETUP_INSTRUCTIONS.md` contient l'ancien guide de setup.
- `docs/TODO.md` contient l'ancien suivi de taches.
- `docs/legacy/` conserve d'anciennes configs non utilisees.

## A ne pas deplacer sans mise a jour des liens

- `font-office/weekly-recap.html`, utilise depuis le front-office.
- `back-office/index.php`, routeur principal admin.
- `app/api/*`, appeles directement par JavaScript.
- `uploads/proofs/`, stockage des preuves utilisateur: images et videos.
- `proof_ai_reviews`, table des suggestions IA associees aux preuves. La decision finale reste dans `participation_proofs.review_state`.
