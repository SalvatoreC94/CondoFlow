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
  ├─ hasMany Assembly
  └─ belongsToMany Supplier (via supplier_condominium)

Building (scala/edificio)
  ├─ belongsTo Condominium
  └─ hasMany Unit

Unit
  ├─ belongsTo Condominium
  ├─ belongsTo Building (nullable)
  ├─ millesimi (decimal, nullable — quota di proprietà su 1000, usata per la ripartizione delle rate)
  ├─ belongsToMany User (residenti, via unit_user)
  └─ hasMany InstallmentCharge

Expense (spesa condominiale)
  ├─ belongsTo Condominium, Supplier (nullable)
  └─ belongsTo User (creator: created_by)

Installment (rata condominiale)
  ├─ belongsTo Condominium
  ├─ belongsTo User (creator: created_by)
  ├─ split_method: millesimi | equal
  └─ hasMany InstallmentCharge (una per unità, generata automaticamente alla creazione)

InstallmentCharge (quota di una rata per una singola unità)
  ├─ belongsTo Installment, Unit
  └─ paid, paid_at (segnata manualmente dall'amministratore — nessuna integrazione di pagamento online nell'MVP)

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

Assembly (assemblea condominiale)
  ├─ belongsTo Condominium
  ├─ belongsTo User (creator: created_by)
  ├─ belongsTo Document (minutes_document_id, nullable — il verbale)
  ├─ type: ordinary | extraordinary, status: scheduled | held | cancelled
  └─ hasMany AssemblyResolution (delibere)

AssemblyResolution (delibera)
  ├─ belongsTo Assembly
  └─ outcome: approved | rejected | postponed

AuditLog — log applicativo per le operazioni sensibili (login, inviti, modifiche a condomini/ticket, cambi password)
```

### Enum applicativi (`app/Enums`)

`UserRole`, `UserStatus`, `UnitType`, `UnitUserRelationship`, `TicketPriority`, `TicketStatus`, `AnnouncementPriority`, `AnnouncementAudience`, `DocumentVisibility`, `SplitMethod` (millesimi | equal, per la ripartizione delle rate), `AssemblyType`, `AssemblyStatus`, `ResolutionOutcome`. `TicketStatus` incapsula anche la macchina a stati (`allowedTransitions()`), così un ticket non può mai saltare da `new` a `resolved` senza passare dagli stati intermedi.

### Migrations

Tutte le tabelle sono in `backend/database/migrations`, con foreign key esplicite (incluse quelle verso `condominiums`, che richiedono un nome tabella esplicito per via della pluralizzazione irregolare di "condominium"). Soft delete su `users`, `condominiums`, `units`, `tickets`, `suppliers`, `documents`, `announcements`.

## Multi-tenancy

La confine di tenant **non è un singolo `tenant_id` globale**, ma dipende dal ruolo:

| Ruolo | Confine di accesso |
|---|---|
| `administrator` | Condomini con `condominiums.administrator_id = auth()->id()` |
| `caretaker` | Condomini presenti nella pivot `caretaker_condominium` |
| `condomino` | Condomini raggiungibili tramite le proprie `units` (pivot `unit_user`) |

Questa logica è centralizzata in `App\Policies\Concerns\ChecksCondominiumAccess` (metodi `administers()`, `caretakes()`, `residesIn()`, `hasAccessTo()`, `isStaffFor()`) e riusata da **ogni** Policy (`CondominiumPolicy`, `UnitPolicy`, `TicketPolicy`, `AnnouncementPolicy`, `DocumentPolicy`, `SupplierPolicy`, `BuildingPolicy`, `ExpensePolicy`, `InstallmentPolicy`, `AssemblyPolicy`). Nessun controller applica la propria logica di scoping: delega sempre alla Policy tramite `$this->authorize(...)`.

**Protezione anti-IDOR:** ogni endpoint che riceve un ID di risorsa (`{condominium}`, `{ticket}`, `{unit}`, `{document}`, …) tramite route-model-binding risolve prima il modello dal database, poi verifica l'accesso tramite Policy — quindi cambiare manualmente un ID nell'URL o nel body di una richiesta non consente mai di raggiungere dati di un altro tenant: la richiesta viene rifiutata con `403`, anche quando la risorsa esiste realmente (non si rivela mai con un `404` selettivo se la risorsa esiste per un altro tenant, per non far trapelare informazioni). Questo comportamento è verificato esplicitamente da `tests/Feature/MultiTenancy/CrossTenantAccessTest.php`.

## Autenticazione

Laravel Sanctum in modalità **SPA (cookie/sessione)**, non token bearer: la SPA e l'API condividono lo stesso dominio "stateful" (in sviluppo tramite il proxy di Vite, in produzione tramite `SANCTUM_STATEFUL_DOMAINS`/CORS), il login imposta un cookie di sessione HttpOnly e ogni richiesta successiva è autenticata automaticamente, con protezione CSRF (`X-XSRF-TOKEN`) gestita da Axios.

**Login via email o cellulare:** `users.email` è nullable e `users.phone` è univoco; `POST /api/login` accetta un campo `identifier` (email o numero di cellulare) invece di un campo `email` fisso — `AuthController@login` risolve l'utente con `WHERE email = ? OR phone = ?` e verifica la password con `Hash::check` (bypassando `Auth::attempt`, che conosce solo una colonna fissa). Nessuna verifica SMS/OTP è implementata: il numero di cellulare è un identificativo alternativo all'email, non un fattore verificato.

Flusso di onboarding (nessuna registrazione libera):

```
Amministratore invita un utente (POST /condominiums/{id}/invitations)
        │  email o phone (almeno uno dei due, richiesto in mutua esclusione)
        │  crea User con status=invited + invitation_token (scade in 7 giorni)
        │  associa l'utente all'unità (condomino) o al condominio (custode)
        │  se ha un'email → invia email con link verso /accetta-invito/{token}
        │  altrimenti → l'endpoint restituisce invitation_url in risposta,
        │    che l'amministratore condivide manualmente (SMS, WhatsApp…)
        ▼
Utente apre il link, imposta la password (POST /invitations/{token}/accept)
        │  status → active, invitation_token → null
        │  login automatico (sessione avviata)
        ▼
L'utente vede solo il proprio condominio/le proprie unità
```

Password reset standard di Laravel (`/forgot-password`, `/reset-password`), con link email che punta al frontend (`ResetPassword::createUrlUsing` in `AppServiceProvider`) — disponibile solo per gli utenti con un'email registrata.

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
| Contabilità | `GET/POST /condominiums/{id}/expenses`, `PUT/DELETE /expenses/{id}`, `GET/POST /condominiums/{id}/installments`, `GET/DELETE /installments/{id}`, `PATCH /installment-charges/{id}` (segna pagata/da pagare), `GET /me/charges` (le quote del condomino autenticato) |
| Assemblee | `GET/POST /assemblies` (`?condominium_id=`), `GET/PUT/DELETE /assemblies/{id}`, `POST /assemblies/{id}/resolutions`, `DELETE /assembly-resolutions/{id}`, `POST /assemblies/{id}/minutes` (upload verbale) |
| Dashboard | `GET /dashboard/stats` (statistiche reali, filtrabili per condominio/periodo) |
| Notifiche | `GET /notifications`, mark as read, `GET /push/vapid-public-key`, `POST/DELETE /push-subscriptions` |

Le risposte usano API Resource dedicate (`app/Http/Resources`) — mai i model Eloquent esposti direttamente — e la paginazione usa il formato standard di Laravel (`AnonymousResourceCollection` su query paginate).

### Contabilità

`App\Services\InstallmentSplitter` calcola la ripartizione di una rata (`Installment`) tra le unità di un condominio e crea le `InstallmentCharge` corrispondenti in un'unica transazione. Per evitare i classici errori di arrotondamento in virgola mobile sul denaro, la ripartizione lavora sempre in **centesimi interi** con il metodo del resto più grande (*largest-remainder method*): ogni quota viene arrotondata per difetto, poi i centesimi mancanti vengono assegnati uno a uno alle unità con la parte frazionaria più alta — così la somma delle quote coincide sempre esattamente con l'importo totale della rata, mai un centesimo perso o in eccesso. La ripartizione `millesimi` richiede che tutte le unità del condominio abbiano `millesimi` impostato (altrimenti la richiesta è rifiutata con un messaggio esplicito); la ripartizione `equal` divide l'importo in parti uguali indipendentemente dai millesimi. Solo l'amministratore del condominio può gestire spese/rate e segnare le quote come pagate (`CondominiumPolicy@manageFinances`); un condomino vede solo le quote delle proprie unità (`GET /me/charges`).

### Assemblee

Un'`Assembly` viene convocata dall'amministratore con ordine del giorno, tipo (ordinaria/straordinaria), data/luogo; alla creazione notifica tutti i residenti del condominio (`Condominium::residents()`). Dopo lo svolgimento, l'amministratore la segna come `held` (o `cancelled`) tramite l'endpoint di update generico e registra le `AssemblyResolution` (delibere, con esito approvata/respinta/rinviata). Il verbale non è un campo binario sull'assemblea: `POST /assemblies/{id}/minutes` crea un `Document` vero e proprio (categoria "Verbali", creata al volo se non esiste — `firstOrCreate`, così la feature non dipende dal seed demo) e lo collega tramite `minutes_document_id`, riusando quindi tutta l'infrastruttura di storage/download già esistente per i documenti. Solo l'amministratore gestisce assemblee/delibere/verbale (`AssemblyPolicy`); staff e condòmini del condominio vedono in lettura tutte le assemblee (nessuna targeting per audience, a differenza degli annunci — un'assemblea riguarda sempre l'intero condominio).

### Upload e download file

- Validazione MIME esplicita (`mimes:jpg,jpeg,png,webp,heic,pdf` per gli allegati ticket; PDF/Office/immagini per i documenti) e limite dimensione.
- Nome file generato lato server (`Str::uuid()`), mai il nome fornito dal client.
- Storage su disco `local` (privato, non pubblicamente accessibile via URL diretto).
- Download **sempre** tramite un endpoint autenticato e verificato da Policy (`TicketAttachmentController@download`, `DocumentController@download`), che effettua lo streaming del file solo dopo aver confermato che l'utente può vedere la risorsa padre — mai un link statico pubblico.

## Notifiche

`App\Notifications` (Laravel Notifications, canali `database` + `mail` dove rilevante + `webpush` per gli eventi più time-sensitive): invito utente, nuova segnalazione, cambio di stato, nuovo commento, comunicazione pubblicata, documento pubblicato, intervento completato, assemblea convocata. In sviluppo `MAIL_MAILER=log` scrive le email nel log invece di inviarle davvero. Le notifiche sono sincrone nell'MVP per semplicità di demo; l'infrastruttura per le code (`QUEUE_CONNECTION=database`, tabella `jobs` già migrata) è pronta per essere attivata (`php artisan queue:work`) quando il volume lo richiederà.

### Notifiche push (Web Push)

Canale `webpush` via `laravel-notification-channels/webpush` (+ `minishlink/web-push` per il protocollo Web Push/VAPID) — nessun servizio di terze parti come Firebase: le notifiche vengono firmate con una coppia di chiavi VAPID generate localmente (`php artisan webpush:vapid`, scrive `VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY` in `.env`) e inviate direttamente ai servizi push nativi del browser (FCM per Chrome, Mozilla Push per Firefox, ecc.).

- **Backend**: `User` usa il trait `HasPushSubscriptions` del pacchetto (tabella `push_subscriptions`, polimorfica); `PushSubscriptionController` espone `GET /push/vapid-public-key` (la chiave pubblica, non segreta, serve al frontend per sottoscriversi) e `POST`/`DELETE /push-subscriptions`. Ogni notifica che vale la pena ricevere subito (`TicketCreated`, `TicketStatusChanged`, `TicketCommented`, `AnnouncementPublished`, `DocumentPublished`, `InterventionCompleted`, `AssemblyScheduled`) include `WebPushChannel::class` in `via()` e implementa `toWebPush()` con titolo/corpo/URL di destinazione (`data.url`, usato dal service worker per il deep-link al click).
- **Nessun crash se non configurato**: se `VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY` sono vuote, il canale semplicemente non invia nulla — nessun errore, le altre notifiche (database/mail) restano invariate. `AppServiceProvider::boot()` sovrascrive il binding del client `WebPush` del pacchetto per passargli il logger di Laravel invece di lasciarlo ricadere su `trigger_error()`: senza `ext-gmp`/`ext-bcmath` (entrambe opzionali) la libreria emette un avviso di performance ad ogni invio, e senza un logger esplicito quell'avviso passa dall'error handler di Laravel come una vera eccezione — interrompendo la richiesta che stava inviando la notifica. Con il logger esplicito l'avviso finisce nel log applicativo, non nel flusso della richiesta.
- **Frontend**: `vite-plugin-pwa` usa la strategia `injectManifest` (non `generateSW`) proprio per poter scrivere un service worker custom (`frontend/src/sw.js`) che, oltre al precache dell'app shell e alla cache `NetworkFirst` per `/api` (stessa logica di prima, riscritta con Workbox esplicito), gestisce anche gli eventi `push` (mostra la notifica) e `notificationclick` (apre/porta in primo piano la relativa schermata). `src/lib/push.js` incapsula la sottoscrizione (`PushManager.subscribe()` con la chiave VAPID pubblica) e la UI di attivazione/disattivazione è nella pagina Profilo, condivisa da tutti i ruoli.

## AI

Nessuna chiamata a servizi AI esterni avviene nell'MVP. `ANTHROPIC_API_KEY` è predisposta in `.env.example` ma **non usata**: è un segnaposto per feature future (classificazione automatica dei ticket, suggerimento priorità, riassunto segnalazioni, ricerca documentale RAG, assistente amministratore), che andranno implementate in un `App\Services\AI` dedicato con un controllo esplicito "se la chiave non è configurata, la feature resta disattivata" — mai una chiamata silenziosa a un servizio esterno.

## PWA

`vite-plugin-pwa` (strategia `injectManifest`, service worker custom in `frontend/src/sw.js`) genera manifest, precache dell'app shell, `NetworkFirst` per le chiamate `/api` e fallback offline (naviga verso la shell cache anche senza rete; i dati richiedono comunque la connessione), oltre a gestire push/notificationclick (vedi [Notifiche push](#notifiche-push-web-push)). Icone e splash ottimizzate per l'installazione su iOS (meta `apple-mobile-web-app-*`) e Android.

## Roadmap

**Già pronto per l'estensione** (nessuna modifica strutturale richiesta):
- Calendario manutenzioni/scadenze → estensione naturale di `Intervention`
- Contratti fornitori → nuova entità collegata a `Supplier`
- Pagamenti online delle rate (Stripe) e piani SaaS (Starter/Professional/Studio) → la ripartizione spese/rate (`Expense`/`Installment`/`InstallmentCharge`) e il modello `Condominium.administrator_id` sono già i punti di aggancio naturali; oggi le quote sono segnate come pagate manualmente dall'amministratore. Il billing dell'abbonamento SaaS stesso (a carico dell'amministratore) è un'integrazione Stripe separata, ortogonale alla contabilità condominiale: si aggancia a `User`/organizzazione, non a `Condominium`
- Verifica SMS/OTP del numero di cellulare, invio automatico dell'invito via SMS/WhatsApp Business → il campo `users.phone` e il flusso di invito già lo prevedono come identificativo; manca solo l'integrazione con un provider (es. Twilio) per l'invio automatico e la verifica
- Presenze/deleghe alle assemblee, votazioni digitali → estensione naturale di `Assembly`, oggi limitata a convocazione/delibere/verbale (nessun tracciamento di chi ha partecipato o come ha votato)
- Custom branding per amministratore → colonna su `Condominium` o nuova tabella `administrator_settings`
- Analytics avanzati → i dati sono già normalizzati (`ticket_status_history`, `interventions`) per costruire report storici senza modifiche allo schema
- AI assistant / ricerca documentale RAG → vedi sezione [AI](#ai)

**Non implementato nell'MVP per scelta esplicita** (P2, per non compromettere la stabilità di P0/P1): analytics avanzati, integrazioni esterne, pagamenti online.
