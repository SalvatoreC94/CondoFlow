# CondoFlow — Architettura

## Principio guida

> Primo condominio reale, ma SaaS fin dal primo giorno.

Nessuna risorsa condominiale è mai legata implicitamente a un solo condominio "corrente": ogni record che appartiene a un condominio porta esplicitamente un `condominium_id`, e ogni endpoint verifica — lato server, tramite Policy — che l'utente autenticato abbia effettivamente accesso a quel condominio, indipendentemente da cosa viene passato nella richiesta.

## Panoramica architetturale

```
┌─────────────────────────┐        cookie/session (Sanctum)       ┌──────────────────────────┐
│   frontend/ (Vue 3 SPA)  │ ─────────────────────────────────────▶│   backend/ (Laravel API) │
│   - Vue Router (guard)   │◀───────────────────────────────────── │   - Controllers          │
│   - Pinia (auth/tenant)  │              JSON REST                │   - Form Requests        │
│   - Axios + CSRF cookie  │                                        │   - Policies             │
│   PWA (installabile)     │                                        │   - Eloquent Models      │
└─────────────────────────┘                                        └──────────┬───────────────┘
                                                                                │
                                                                     MySQL / SQLite
```

Separazione netta frontend/backend: la SPA non contiene alcuna logica di autorizzazione — è **solo UI**. Ogni decisione "chi può vedere/fare cosa" è applicata server-side tramite Laravel Policies, e la UI si limita a nascondere azioni non disponibili per comodità d'uso.

## Modello dati

### Entità e relazioni principali

```
User (role: administrator | caretaker | condomino)
  ├─ administeredCondominiums()  hasMany   Condominium   (administrator_id)
  ├─ assignedCondominiums()      belongsToMany Condominium  (caretaker_condominium)
  └─ units()                     belongsToMany Unit         (unit_user: relationship, is_primary)

Condominium
  ├─ hasMany Building
  ├─ hasMany Unit
  ├─ belongsToMany User (caretakers, via caretaker_condominium)
  ├─ hasMany Ticket
  ├─ hasMany Announcement
  ├─ hasMany Document
  └─ belongsToMany Supplier (via supplier_condominium)

Building (scala/edificio)
  ├─ belongsTo Condominium
  └─ hasMany Unit

Unit
  ├─ belongsTo Condominium
  ├─ belongsTo Building (nullable)
  └─ belongsToMany User (residenti, via unit_user)

Ticket (segnalazione)
  ├─ belongsTo Condominium, Unit (nullable), TicketCategory
  ├─ belongsTo User (reporter: created_by)
  ├─ belongsTo User (assigned_caretaker_id, nullable)
  ├─ belongsTo Supplier (nullable)
  ├─ hasMany TicketComment (is_internal: nota interna vs. visibile al condomino)
  ├─ hasMany TicketAttachment
  ├─ hasMany TicketStatusHistory
  └─ hasMany Intervention

Supplier (fornitore)
  ├─ belongsTo User (administrator_id — il fornitore appartiene all'amministratore, non al condominio)
  ├─ belongsToMany Condominium (supplier_condominium)
  └─ hasMany SupplierContact

Document
  ├─ belongsTo Condominium, DocumentCategory
  └─ visibility: all | administrators | caretakers | condomini

Announcement (comunicazione)
  ├─ belongsTo Condominium
  ├─ audience: all | buildings | users
  ├─ belongsToMany Building (se audience=buildings)
  └─ belongsToMany User (recipients, se audience=users; reads, per il "letto/non letto")

AuditLog — log applicativo per le operazioni sensibili (login, inviti, modifiche a condomini/ticket, cambi password)
```

### Enum applicativi (`app/Enums`)

`UserRole`, `UserStatus`, `UnitType`, `UnitUserRelationship`, `TicketPriority`, `TicketStatus`, `AnnouncementPriority`, `AnnouncementAudience`, `DocumentVisibility`. `TicketStatus` incapsula anche la macchina a stati (`allowedTransitions()`), così un ticket non può mai saltare da `new` a `resolved` senza passare dagli stati intermedi.

### Migrations

Tutte le tabelle sono in `backend/database/migrations`, con foreign key esplicite (incluse quelle verso `condominiums`, che richiedono un nome tabella esplicito per via della pluralizzazione irregolare di "condominium"). Soft delete su `users`, `condominiums`, `units`, `tickets`, `suppliers`, `documents`, `announcements`.

## Multi-tenancy

La confine di tenant **non è un singolo `tenant_id` globale**, ma dipende dal ruolo:

| Ruolo | Confine di accesso |
|---|---|
| `administrator` | Condomini con `condominiums.administrator_id = auth()->id()` |
| `caretaker` | Condomini presenti nella pivot `caretaker_condominium` |
| `condomino` | Condomini raggiungibili tramite le proprie `units` (pivot `unit_user`) |

Questa logica è centralizzata in `App\Policies\Concerns\ChecksCondominiumAccess` (metodi `administers()`, `caretakes()`, `residesIn()`, `hasAccessTo()`, `isStaffFor()`) e riusata da **ogni** Policy (`CondominiumPolicy`, `UnitPolicy`, `TicketPolicy`, `AnnouncementPolicy`, `DocumentPolicy`, `SupplierPolicy`, `BuildingPolicy`). Nessun controller applica la propria logica di scoping: delega sempre alla Policy tramite `$this->authorize(...)`.

**Protezione anti-IDOR:** ogni endpoint che riceve un ID di risorsa (`{condominium}`, `{ticket}`, `{unit}`, `{document}`, …) tramite route-model-binding risolve prima il modello dal database, poi verifica l'accesso tramite Policy — quindi cambiare manualmente un ID nell'URL o nel body di una richiesta non consente mai di raggiungere dati di un altro tenant: la richiesta viene rifiutata con `403`, anche quando la risorsa esiste realmente (non si rivela mai con un `404` selettivo se la risorsa esiste per un altro tenant, per non far trapelare informazioni). Questo comportamento è verificato esplicitamente da `tests/Feature/MultiTenancy/CrossTenantAccessTest.php`.

## Autenticazione

Laravel Sanctum in modalità **SPA (cookie/sessione)**, non token bearer: la SPA e l'API condividono lo stesso dominio "stateful" (in sviluppo tramite il proxy di Vite, in produzione tramite `SANCTUM_STATEFUL_DOMAINS`/CORS), il login imposta un cookie di sessione HttpOnly e ogni richiesta successiva è autenticata automaticamente, con protezione CSRF (`X-XSRF-TOKEN`) gestita da Axios.

Flusso di onboarding (nessuna registrazione libera):

```
Amministratore invita un utente (POST /condominiums/{id}/invitations)
        │  crea User con status=invited + invitation_token (scade in 7 giorni)
        │  associa l'utente all'unità (condomino) o al condominio (custode)
        │  invia email con link verso /accetta-invito/{token}
        ▼
Utente apre il link, imposta la password (POST /invitations/{token}/accept)
        │  status → active, invitation_token → null
        │  login automatico (sessione avviata)
        ▼
L'utente vede solo il proprio condominio/le proprie unità
```

Password reset standard di Laravel (`/forgot-password`, `/reset-password`), con link email che punta al frontend (`ResetPassword::createUrlUsing` in `AppServiceProvider`).

## Autorizzazione

Ogni model sensibile ha una Policy dedicata in `app/Policies`, con autodiscovery basata sulla convenzione dei nomi di Laravel (nessuna registrazione manuale necessaria). Esempio (`TicketPolicy`):

- `view`: staff del condominio (admin/custode) **oppure** il condomino che ha creato il ticket o che appartiene alla stessa unità
- `update` / `updateStatus` / `deleteAttachment`: solo staff
- `delete`: solo amministratore
- `comment`: chiunque possa vedere il ticket (i commenti interni sono visibili solo allo staff, filtrati sia in lettura che vietati in scrittura ai condòmini)

I Form Request (`app/Http/Requests`) applicano sia validazione dei dati sia, quando serve una risorsa già esistente, l'autorizzazione (`authorize()` richiama la Policy). I permessi non dipendono mai dalla UI: la SPA nasconde pulsanti/voci di menu solo per comodità, ma ogni azione è comunque riverificata server-side.

## API

Tutte le rotte sono sotto `/api`, protette da `auth:sanctum` (eccetto login, inviti, reset password). Risorse principali (`backend/routes/api/tenant.php`):

| Risorsa | Endpoint |
|---|---|
| Condomini | `GET/POST /condominiums`, `GET/PUT/DELETE /condominiums/{id}` |
| Edifici/scale | `GET/POST /condominiums/{id}/buildings`, `PUT/DELETE /buildings/{id}` |
| Unità | `GET/POST /condominiums/{id}/units`, `GET/PUT/DELETE /units/{id}`, gestione residenti |
| Persone | `GET /condominiums/{id}/users`, inviti, rimozione custodi |
| Segnalazioni | `apiResource /tickets`, `PATCH /tickets/{id}/status`, commenti, allegati (upload/download protetto), interventi |
| Comunicazioni | `apiResource /announcements`, `POST /announcements/{id}/read` |
| Documenti | `GET/POST /documents`, `GET /documents/{id}/download` (streaming autenticato) |
| Fornitori | `apiResource /suppliers`, contatti |
| Dashboard | `GET /dashboard/stats` (statistiche reali, filtrabili per condominio/periodo) |
| Notifiche | `GET /notifications`, mark as read |

Le risposte usano API Resource dedicate (`app/Http/Resources`) — mai i model Eloquent esposti direttamente — e la paginazione usa il formato standard di Laravel (`AnonymousResourceCollection` su query paginate).

### Upload e download file

- Validazione MIME esplicita (`mimes:jpg,jpeg,png,webp,heic,pdf` per gli allegati ticket; PDF/Office/immagini per i documenti) e limite dimensione.
- Nome file generato lato server (`Str::uuid()`), mai il nome fornito dal client.
- Storage su disco `local` (privato, non pubblicamente accessibile via URL diretto).
- Download **sempre** tramite un endpoint autenticato e verificato da Policy (`TicketAttachmentController@download`, `DocumentController@download`), che effettua lo streaming del file solo dopo aver confermato che l'utente può vedere la risorsa padre — mai un link statico pubblico.

## Notifiche

`App\Notifications` (Laravel Notifications, canali `database` + `mail` dove rilevante): invito utente, nuova segnalazione, cambio di stato, nuovo commento, comunicazione pubblicata, documento pubblicato, intervento completato. In sviluppo `MAIL_MAILER=log` scrive le email nel log invece di inviarle davvero. Le notifiche sono sincrone nell'MVP per semplicità di demo; l'infrastruttura per le code (`QUEUE_CONNECTION=database`, tabella `jobs` già migrata) è pronta per essere attivata (`php artisan queue:work`) quando il volume lo richiederà.

## AI

Nessuna chiamata a servizi AI esterni avviene nell'MVP. `ANTHROPIC_API_KEY` è predisposta in `.env.example` ma **non usata**: è un segnaposto per feature future (classificazione automatica dei ticket, suggerimento priorità, riassunto segnalazioni, ricerca documentale RAG, assistente amministratore), che andranno implementate in un `App\Services\AI` dedicato con un controllo esplicito "se la chiave non è configurata, la feature resta disattivata" — mai una chiamata silenziosa a un servizio esterno.

## PWA

`vite-plugin-pwa` genera manifest, service worker (precache dell'app shell + `NetworkFirst` per le chiamate `/api`) e fallback offline (naviga verso la shell cache anche senza rete; i dati richiedono comunque la connessione). Icone e splash ottimizzate per l'installazione su iOS (meta `apple-mobile-web-app-*`) e Android.

## Roadmap

**Già pronto per l'estensione** (nessuna modifica strutturale richiesta):
- Gestione assemblee/verbali → nuova entità collegata a `Condominium`, riusa `Document` per i verbali
- Calendario manutenzioni/scadenze → estensione naturale di `Intervention`
- Contratti fornitori → nuova entità collegata a `Supplier`
- Gestione spese/pagamenti, integrazione Stripe, piani SaaS (Starter/Professional/Studio) → il modello `Condominium.administrator_id` è già il punto di aggancio naturale per il billing per amministratore
- Integrazione WhatsApp Business / push notification → nuovi canali su `App\Notifications`, che già astraggono il canale di invio
- Custom branding per amministratore → colonna su `Condominium` o nuova tabella `administrator_settings`
- Analytics avanzati → i dati sono già normalizzati (`ticket_status_history`, `interventions`) per costruire report storici senza modifiche allo schema
- AI assistant / ricerca documentale RAG → vedi sezione [AI](#ai)

**Non implementato nell'MVP per scelta esplicita** (P2, per non compromettere la stabilità di P0/P1): analytics avanzati, integrazioni esterne, billing.
