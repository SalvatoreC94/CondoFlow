# CondoFlow

CondoFlow è un micro-SaaS multi-tenant per amministratori di condominio: gestione di condomini, unità immobiliari, condòmini, segnalazioni (ticket), comunicazioni, documenti e fornitori, con un'app mobile-first installabile (PWA) per i condòmini e i custodi.

Il primo ambiente reale di riferimento è **"Parco Nuova California"**, un condominio fronte mare di 140 appartamenti — ma l'applicazione è progettata fin dal primo giorno per gestire più condomini, ciascuno con i propri utenti, segnalazioni, comunicazioni e documenti, completamente isolati tra loro (multi-tenancy).

## Indice

- [Stack tecnologico](#stack-tecnologico)
- [Struttura del repository](#struttura-del-repository)
- [Requisiti](#requisiti)
- [Installazione](#installazione)
- [Configurazione `.env`](#configurazione-env)
- [Database e seed](#database-e-seed)
- [Avvio in sviluppo](#avvio-in-sviluppo)
- [Credenziali demo](#credenziali-demo)
- [Testing](#testing)
- [Build di produzione](#build-di-produzione)
- [Qualità del codice](#qualità-del-codice)
- [Documentazione architetturale](#documentazione-architetturale)

## Stack tecnologico

**Backend**
- Laravel 12 / PHP 8.4
- Laravel Sanctum (autenticazione SPA basata su sessione/cookie)
- MySQL in produzione, SQLite per sviluppo/test (nessun servizio esterno richiesto)
- Laravel Notifications (in-app + email), Queue pronte all'uso (`QUEUE_CONNECTION=database`)
- Pest per i test

**Frontend**
- Vue 3 (Composition API, `<script setup>`) + Vite
- Tailwind CSS 4
- Vue Router 4, Pinia
- `vite-plugin-pwa` per l'installabilità mobile (iOS/Android)

## Struttura del repository

```
CondoFlow/
├── backend/     Laravel API (app, database, routes, tests)
└── frontend/    SPA Vue 3 (PWA)
```

## Requisiti

- PHP >= 8.3 con estensioni `pdo_sqlite` (o `pdo_mysql` in produzione), `gd`, `fileinfo`, `mbstring`
- Composer 2
- Node.js >= 20 e npm
- MySQL 8 (consigliato in produzione) — non necessario in locale, si usa SQLite

## Installazione

```bash
git clone <repo-url> CondoFlow
cd CondoFlow

# Backend
cd backend
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite   # solo se si usa SQLite (default di sviluppo)
php artisan migrate --seed

# Frontend
cd ../frontend
cp .env.example .env
npm install
```

## Configurazione `.env`

### Backend (`backend/.env`)

Variabili principali (vedi `backend/.env.example` per l'elenco completo):

| Variabile | Descrizione |
|---|---|
| `DB_CONNECTION` | `sqlite` in sviluppo/CI, `mysql` in produzione (vedi sotto) |
| `FRONTEND_URL` | Origine della SPA (es. `http://localhost:5173`), usata per i link nelle email e per Sanctum |
| `SANCTUM_STATEFUL_DOMAINS` | Domini autorizzati all'autenticazione via cookie/sessione |
| `MAIL_MAILER` | `log` in sviluppo (le email vengono scritte nel log invece di essere inviate) |
| `ANTHROPIC_API_KEY` | Opzionale, per le funzionalità AI future (vedi [ARCHITECTURE.md](ARCHITECTURE.md#ai)) — se vuota, nessuna feature AI è attiva |

**Passare a MySQL in produzione:**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=condoflow
DB_USERNAME=condoflow
DB_PASSWORD=secret
```

Le migrations sono scritte in modo portabile e funzionano identiche su MySQL e SQLite.

### Frontend (`frontend/.env`)

| Variabile | Descrizione |
|---|---|
| `VITE_API_URL` | Vuoto in sviluppo (il dev server di Vite fa da proxy verso `http://localhost:8000`, vedi `vite.config.js`); in produzione l'origine pubblica dell'API |

## Database e seed

```bash
cd backend
php artisan migrate:fresh --seed
```

Il seeder (`database/seeders/DemoSeeder.php`) crea un dataset realistico incentrato su un unico condominio:

- **Parco Nuova California** — 140 unità (5 scale × 7 piani × 4 unità), 50 condòmini, 2 custodi
- 12 categorie di segnalazione, 7 categorie documentali, 10 fornitori
- ~40 segnalazioni distribuite su tutti gli stati, con storico, commenti e alcune foto allegate
- Comunicazioni (a tutto il condominio e per scala) e documenti demo

La struttura resta comunque multi-tenant fin dal primo giorno (un amministratore può gestire più condomini, un condomino/custode vede solo i propri) — l'isolamento tra tenant è verificato dalla suite di test automatici (`tests/Feature/MultiTenancy/CrossTenantAccessTest.php`), che costruisce i propri condomini/amministratori isolati indipendentemente dai dati demo.

Nessun dato personale reale è utilizzato: nomi, email e indirizzi sono generati con Faker (locale `it_IT`).

## Avvio in sviluppo

In due terminali separati:

```bash
# Terminale 1 — API
cd backend
php artisan serve
# oppure: composer dev  (avvia anche queue listener e log viewer)

# Terminale 2 — SPA
cd frontend
npm run dev
```

Apri **http://localhost:5173**. Il dev server di Vite proxya automaticamente `/api` e `/sanctum` verso `http://localhost:8000`, quindi l'autenticazione via cookie funziona senza configurazione CORS aggiuntiva in locale.

## Credenziali demo

Password comune per tutti gli utenti demo: **`password`**

| Ruolo | Email | Condominio |
|---|---|---|
| Amministratore | `admin@condoflow.test` | Parco Nuova California |
| Custode | `custode1@condoflow.test` | Parco Nuova California |
| Custode | `custode2@condoflow.test` | Parco Nuova California |
| Condomino | `condomino0@condoflow.test` … `condomino49@condoflow.test` | Parco Nuova California |

## Testing

### Backend (Pest)

```bash
cd backend
php artisan test
# oppure
./vendor/bin/pest
```

La suite copre autenticazione, inviti, **multi-tenancy e protezione da IDOR** (accesso cross-tenant tramite manipolazione di ID nelle richieste API), ciclo di vita delle segnalazioni (creazione, transizioni di stato, commenti interni/pubblici, allegati), visibilità delle comunicazioni per pubblico, visibilità dei documenti per ruolo, assegnazione fornitori/interventi.

### Frontend

```bash
cd frontend
npm run build   # verifica TypeScript/build a zero errori
```

## Build di produzione

```bash
# Backend
cd backend
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan migrate --force

# Frontend
cd frontend
npm run build   # genera frontend/dist, pronto per essere servito da un CDN/webserver
```

In produzione servire `frontend/dist` da un webserver/CDN con `VITE_API_URL` puntato all'origine pubblica dell'API, e configurare `SANCTUM_STATEFUL_DOMAINS`/CORS lato backend di conseguenza.

## Qualità del codice

```bash
# Backend
cd backend && ./vendor/bin/pint

# Frontend
cd frontend && npm run build   # la build fallisce su errori Vue/JS
```

## Documentazione architetturale

Per il dettaglio di modello dati, multi-tenancy, autenticazione/autorizzazione, API e roadmap, vedi **[ARCHITECTURE.md](ARCHITECTURE.md)**.
