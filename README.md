# CondoFlow

CondoFlow è un micro-SaaS multi-tenant per amministratori di condominio: gestione di condomini, unità immobiliari, condòmini, segnalazioni (ticket), comunicazioni, documenti, fornitori, contabilità (spese e rate condominiali) e assemblee (convocazioni, delibere, verbali), con un'app mobile-first installabile (PWA) per i condòmini e i custodi, con notifiche push oltre a quelle in-app/email. La registrazione e il login funzionano sia via email sia con il solo numero di cellulare.

Oltre alla SPA rivolta agli amministratori di condominio (i clienti paganti), esiste un **pannello piattaforma** separato (`/platform`, basato su Filament) a uso dell'operatore SaaS — chi vende e gestisce l'abbonamento a CondoFlow — per tenere sotto controllo i clienti, il loro stato di abbonamento e i dati di riferimento condivisi. Vedi [Pannello piattaforma](#pannello-piattaforma-operatore-saas).

Il primo ambiente reale di riferimento è **"Parco Nuova California"**, un condominio fronte mare di 135 unità (45 colonne su 3 piani) — ma l'applicazione è progettata fin dal primo giorno per gestire più condomini, ciascuno con i propri utenti, segnalazioni, comunicazioni, documenti e contabilità, completamente isolati tra loro (multi-tenancy).

## Indice

- [Stack tecnologico](#stack-tecnologico)
- [Struttura del repository](#struttura-del-repository)
- [Requisiti](#requisiti)
- [Installazione](#installazione)
- [Configurazione `.env`](#configurazione-env)
- [Database e seed](#database-e-seed)
- [Avvio in sviluppo](#avvio-in-sviluppo)
- [Credenziali demo](#credenziali-demo)
- [Pannello piattaforma (operatore SaaS)](#pannello-piattaforma-operatore-saas)
- [Testing](#testing)
- [Build di produzione](#build-di-produzione)
- [Qualità del codice](#qualità-del-codice)
- [Documentazione architetturale](#documentazione-architetturale)

## Stack tecnologico

**Backend**
- Laravel 12 / PHP 8.3
- Laravel Sanctum (autenticazione SPA basata su sessione/cookie, login via email o numero di cellulare)
- MySQL in produzione, SQLite per sviluppo/test (nessun servizio esterno richiesto)
- Laravel Notifications (in-app, email, push via Web Push/VAPID), Queue pronte all'uso (`QUEUE_CONNECTION=database`)
- Filament v5 (`/platform`, backoffice per l'operatore SaaS — separato dalla SPA/API multi-tenant)
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
  - `bcmath` o `gmp` opzionali ma consigliate: senza nessuna delle due, le notifiche push restano funzionanti ma scrivono un avviso di performance nel log a ogni invio
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
php artisan webpush:vapid        # genera le chiavi per le notifiche push (facoltativo)

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
| `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` | Chiavi per le notifiche push (Web Push) — generale con `php artisan webpush:vapid`; se vuote il canale push non invia nulla, senza errori |
| `ANTHROPIC_API_KEY` | Opzionale, per le funzionalità AI future (vedi [ARCHITECTURE.md](ARCHITECTURE.md#ai)) — se vuota, nessuna feature AI è attiva |
| `PLATFORM_OPERATOR_EMAIL` / `PLATFORM_OPERATOR_PASSWORD` | Credenziali dell'account operatore seedato da `PlatformUserSeeder` per il [pannello piattaforma](#pannello-piattaforma-operatore-saas) — da sovrascrivere in ogni ambiente reale |

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

- **Parco Nuova California** — 135 unità organizzate in 45 colonne (una per numero civico) × 3 piani (terra, primo, secondo), 50 condòmini, 2 custodi
- 12 categorie di segnalazione, 7 categorie documentali, 10 fornitori
- ~40 segnalazioni distribuite su tutti gli stati, con storico, commenti e alcune foto allegate
- Comunicazioni (a tutto il condominio e per scala) e documenti demo
- Millesimi assegnati a ogni unità, 15 spese e 3 rate condominiali (ripartite per millesimi o in parti uguali), con quote in parte già segnate come pagate
- 3 assemblee demo (due svolte con delibere e verbale/senza verbale, una convocata nel prossimo mese)
- 2 condòmini demo registrati con il solo numero di cellulare (nessuna email), per testare il login via telefono

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

| Ruolo | Email / Cellulare | Condominio |
|---|---|---|
| Amministratore | `admin@condoflow.test` | Parco Nuova California |
| Custode | `custode1@condoflow.test` | Parco Nuova California |
| Custode | `custode2@condoflow.test` | Parco Nuova California |
| Condomino | `condomino0@condoflow.test` … `condomino49@condoflow.test` | Parco Nuova California |
| Condomino (solo cellulare, nessuna email) | `+39 333 1234567` | Parco Nuova California |
| Condomino (solo cellulare, nessuna email) | `+39 333 7654321` | Parco Nuova California |

Il campo "Email o numero di cellulare" nella schermata di login accetta entrambi i formati.

## Pannello piattaforma (operatore SaaS)

`/platform` è il backoffice dell'operatore SaaS (chi gestisce la vendita in abbonamento di CondoFlow), completamente separato dalla SPA Vue usata ogni giorno dagli amministratori di condominio: autenticazione propria (guard `platform`, tabella `platform_users`, nessuna relazione con gli utenti multi-tenant), pensato per la supervisione dei clienti più che per l'operatività quotidiana.

Cosa contiene oggi:

- **Amministratori** — elenco dei clienti (utenti con ruolo `administrator`), creazione via invito (stesso meccanismo email/link della SPA) e gestione manuale dello stato di abbonamento (`trial`/`active`/`suspended`/`cancelled`, piano, scadenza, note interne) — in attesa dell'integrazione Stripe
- **Condomini** — vista di sola lettura su tutti i condomini della piattaforma, a prescindere dall'amministratore
- **Log di controllo** — consultazione di sola lettura di `audit_logs`
- **Categorie segnalazioni / documenti** — CRUD sui dati di riferimento condivisi da tutti i tenant
- **Dashboard** — numero di amministratori (attivi/in prova), condomini gestiti, unità totali sotto gestione, abbonamenti in scadenza nei prossimi 7 giorni

Per accedere in locale, dopo il seed:

```
URL:      http://localhost:8000/platform
Email:    operator@condoflow.test   (o PLATFORM_OPERATOR_EMAIL)
Password: password                  (o PLATFORM_OPERATOR_PASSWORD)
```

Il pagamento dell'abbonamento SaaS (a carico dell'amministratore/cliente, non dei condòmini) è pianificato ma non ancora implementato: oggi lo stato di abbonamento si gestisce manualmente da questo pannello.

## Testing

### Backend (Pest)

```bash
cd backend
php artisan test
# oppure
./vendor/bin/pest
```

La suite copre autenticazione (via email o cellulare), inviti (via email o solo cellulare), **multi-tenancy e protezione da IDOR** (accesso cross-tenant tramite manipolazione di ID nelle richieste API, incluse spese/rate/assemblee), ciclo di vita delle segnalazioni (creazione, transizioni di stato, commenti interni/pubblici, allegati), visibilità delle comunicazioni per pubblico, visibilità dei documenti per ruolo, assegnazione fornitori/interventi, contabilità (ripartizione millesimale/in parti uguali con arrotondamento esatto, autorizzazioni, stato pagamenti), assemblee (convocazione, delibere, caricamento verbale, autorizzazioni), sottoscrizione alle notifiche push, e il [pannello piattaforma](#pannello-piattaforma-operatore-saas) (risorse Filament, dashboard, **isolamento del guard `platform`** dal guard `web` usato dai tenant — un amministratore/condomino/custode non può entrare in `/platform` e viceversa).

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
