# Accordo di trattamento dati (ex art. 28 GDPR) — CondoFlow

> ⚠️ **BOZZA — non è consulenza legale.** Modello di partenza per l'accordo tra CondoFlow (responsabile del trattamento) e ogni amministratore-cliente (titolare del trattamento dei dati dei propri condòmini). **Deve essere rivisto e validato da un avvocato** prima di essere proposto o firmato — in particolare le clausole su sub-responsabili, trasferimenti extra-UE, responsabilità e notifica data breach vanno adattate alla realtà tecnica e contrattuale effettiva del servizio.

## Parti

- **Titolare del trattamento**: l'amministratore di condominio che sottoscrive l'abbonamento a CondoFlow (di seguito "il Cliente"), in qualità di titolare del trattamento dei dati personali dei condòmini/residenti dei condomini che amministra.
- **Responsabile del trattamento**: **[Ragione sociale CondoFlow]** (di seguito "CondoFlow"), in qualità di fornitore della piattaforma software tramite cui il Cliente tratta tali dati.

## 1. Oggetto e durata

Il presente accordo disciplina il trattamento dei dati personali che CondoFlow effettua per conto del Cliente nell'ambito dell'erogazione del servizio SaaS CondoFlow. Ha durata pari a quella del contratto di abbonamento tra le parti; alla cessazione si applica quanto previsto alla clausola 7.

## 2. Natura, finalità e categorie di dati

**Natura del trattamento**: raccolta, registrazione, conservazione, consultazione, modifica, cancellazione dei dati inseriti dal Cliente (o dai suoi condòmini/custodi) nell'applicazione, tramite operazioni automatizzate (hosting, storage, invio di notifiche, backup).

**Finalità**: unicamente l'erogazione delle funzionalità del servizio CondoFlow (gestione anagrafica condominio/unità/condòmini, segnalazioni, comunicazioni, documenti, contabilità condominiale, assemblee, notifiche) secondo le istruzioni documentate del Cliente, così come configurate nell'uso ordinario dell'applicazione.

**Categorie di interessati**: condòmini/residenti, custodi, altri utenti invitati dal Cliente nell'applicazione.

**Categorie di dati personali**:
- dati anagrafici e di contatto (nome, email, numero di cellulare);
- dati identificativi dell'unità immobiliare e del rapporto con essa (proprietario/inquilino);
- contenuto delle segnalazioni (ticket) e dei relativi commenti/allegati;
- documenti condominiali caricati dal Cliente;
- dati contabili (quote, pagamenti) relativi alle singole unità;
- verbali e delibere assembleari;
- [eventuali dati particolari, se il Cliente ne carica tramite allegati/documenti — da valutare se applicabile]

## 3. Obblighi di CondoFlow (responsabile del trattamento)

CondoFlow si impegna a:

1. trattare i dati **solo** su istruzione documentata del Cliente (che si intende data tramite la normale configurazione e uso dell'applicazione), salvo obbligo previsto dal diritto UE o dello Stato membro;
2. garantire che il personale autorizzato al trattamento sia vincolato da un obbligo di riservatezza;
3. adottare le misure di sicurezza tecniche e organizzative adeguate (art. 32 GDPR) — vedi clausola 5;
4. rispettare le condizioni per il ricorso a sub-responsabili — vedi clausola 6;
5. assistere il Cliente, per quanto ragionevolmente possibile, nel dare seguito alle richieste di esercizio dei diritti degli interessati (accesso, rettifica, cancellazione, ecc.);
6. assistere il Cliente nel garantire il rispetto degli artt. 32-36 GDPR (sicurezza, notifica data breach, valutazione d'impatto), tenendo conto della natura del trattamento e delle informazioni a disposizione di CondoFlow;
7. su scelta del Cliente, cancellare o restituire tutti i dati personali al termine della fornitura dei servizi, salvo obbligo di conservazione previsto dal diritto UE o nazionale — vedi clausola 7;
8. mettere a disposizione del Cliente le informazioni necessarie a dimostrare il rispetto degli obblighi di cui all'art. 28 GDPR, e consentire/contribuire ad attività di audit (anche tramite ispettori incaricati dal Cliente), con ragionevole preavviso;
9. informare tempestivamente il Cliente qualora un'istruzione ricevuta violi, a suo parere, il GDPR o altre norme applicabili in materia di protezione dei dati.

## 4. Notifica di violazioni dei dati (data breach)

CondoFlow notifica al Cliente, **senza ingiustificato ritardo** e comunque entro **[XX] ore** dal momento in cui ne viene a conoscenza, qualsiasi violazione dei dati personali trattati, fornendo le informazioni ragionevolmente disponibili per consentire al Cliente di adempiere ai propri obblighi verso il Garante e gli interessati (artt. 33-34 GDPR).

## 5. Misure di sicurezza tecniche e organizzative

Elenco delle misure effettivamente implementate nella piattaforma (da mantenere aggiornato in linea con `ARCHITECTURE.md`):

- Isolamento dei dati tra clienti (multi-tenancy) applicato a livello di autorizzazione su ogni richiesta, non solo in interfaccia — verificato da una suite di test automatici anti-IDOR eseguita ad ogni modifica del codice
- Autenticazione basata su sessione con password sempre salvate come hash (mai in chiaro)
- Download di documenti/allegati sempre tramite endpoint autenticato e autorizzato, mai link pubblici diretti
- Validazione esplicita del tipo file e generazione lato server del nome file per gli upload
- Accesso del personale CondoFlow ai dati del Cliente limitato a un pannello di gestione separato, con autenticazione propria e **log di controllo** di ogni accesso e azione (chi, cosa, quando)
- [Aggiungere: cifratura in transito (TLS), politica di backup e relativa retention, eventuale cifratura dei dati a riposo, politiche di gestione delle password/2FA per il personale, patching e aggiornamento delle dipendenze]

## 6. Sub-responsabili

Il Cliente autorizza in via generale il ricorso da parte di CondoFlow a sub-responsabili del trattamento per l'erogazione del servizio (es. hosting, invio email transazionali, servizi di notifica push), a condizione che:

- CondoFlow imponga a ciascun sub-responsabile obblighi di protezione dei dati equivalenti a quelli del presente accordo;
- CondoFlow mantenga un elenco aggiornato dei sub-responsabili in uso, disponibile su richiesta del Cliente;
- CondoFlow informi il Cliente di eventuali modifiche previste riguardanti l'aggiunta o la sostituzione di sub-responsabili, dando al Cliente la possibilità di opporsi.

**Elenco sub-responsabili attuali**: [da compilare — es. provider di hosting/cloud con relativa localizzazione geografica, provider email transazionale, provider push notification]

## 7. Cessazione del trattamento

Al termine del contratto di abbonamento, salvo diversa istruzione del Cliente o obbligo legale di conservazione, CondoFlow procede, a scelta del Cliente comunicata entro **[XX] giorni** dalla cessazione:

- alla **restituzione** di tutti i dati personali in un formato strutturato e leggibile; oppure
- alla loro **cancellazione definitiva**, inclusa ogni copia esistente (salvo obblighi legali di conservazione, nel qual caso i dati restano protetti e non ulteriormente trattati per altre finalità).

## 8. Responsabilità

[Clausola su limitazione di responsabilità, foro competente, legge applicabile — da definire con un avvocato in coerenza con i termini di servizio generali del contratto di abbonamento.]

---

*Ultimo aggiornamento bozza: da compilare alla pubblicazione.*
