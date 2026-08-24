<?php

namespace Database\Seeders;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementPriority;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UnitType;
use App\Models\Announcement;
use App\Models\Building;
use App\Models\Condominium;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Supplier;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketStatusHistory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Realistic demo dataset for CondoFlow: the "Parco Nuova California"
 * condominium (135 units), one administrator, two caretakers, 50 residents.
 */
class DemoSeeder extends Seeder
{
    /** @var array<int, TicketCategory> */
    private array $ticketCategories = [];

    /** @var array<int, DocumentCategory> */
    private array $documentCategories = [];

    public function run(): void
    {
        $this->seedReferenceData();

        $admin = User::factory()->administrator()->create([
            'name' => 'Giulia Ferretti',
            'email' => 'admin@condoflow.test',
            'password' => bcrypt('password'),
        ]);

        $condominium = $this->seedCondominium($admin);

        $this->command?->info('Demo seed completato.');
        $this->command?->table(['Condominio', 'Unità', 'Amministratore'], [
            [$condominium->name, $condominium->total_units, $admin->email],
        ]);
    }

    private function seedReferenceData(): void
    {
        $ticketCategories = [
            ['name' => 'Acqua', 'icon' => 'droplet'],
            ['name' => 'Elettricità', 'icon' => 'bolt'],
            ['name' => 'Ascensore', 'icon' => 'arrow-up-down'],
            ['name' => 'Cancello', 'icon' => 'door-open'],
            ['name' => 'Illuminazione', 'icon' => 'lightbulb'],
            ['name' => 'Pulizia', 'icon' => 'sparkles'],
            ['name' => 'Giardinaggio', 'icon' => 'leaf'],
            ['name' => 'Parcheggio', 'icon' => 'car'],
            ['name' => 'Piscina', 'icon' => 'waves'],
            ['name' => 'Sicurezza', 'icon' => 'shield'],
            ['name' => 'Riscaldamento', 'icon' => 'thermometer'],
            ['name' => 'Altro', 'icon' => 'ellipsis'],
        ];

        foreach ($ticketCategories as $i => $data) {
            $this->ticketCategories[] = TicketCategory::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'icon' => $data['icon'],
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        $documentCategories = [
            ['name' => 'Regolamento', 'icon' => 'book'],
            ['name' => 'Verbali', 'icon' => 'file-text'],
            ['name' => 'Comunicazioni', 'icon' => 'megaphone'],
            ['name' => 'Assicurazione', 'icon' => 'shield-check'],
            ['name' => 'Manutenzione', 'icon' => 'wrench'],
            ['name' => 'Impianti', 'icon' => 'plug'],
            ['name' => 'Altro', 'icon' => 'folder'],
        ];

        foreach ($documentCategories as $i => $data) {
            $this->documentCategories[] = DocumentCategory::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'icon' => $data['icon'],
                'sort_order' => $i,
            ]);
        }
    }

    private function seedCondominium(User $admin): Condominium
    {
        $condominium = Condominium::create([
            'administrator_id' => $admin->id,
            'name' => 'Parco Nuova California',
            'address' => 'Viale delle Palme, 140',
            'city' => 'Tortora Marina',
            'province' => 'CS',
            'postal_code' => '87020',
            'country' => 'IT',
            'total_units' => 135,
            'description' => 'Grande complesso residenziale fronte mare, 3 piani (terra, primo, secondo), 45 unità per piano.',
        ]);

        // 2 custodi assegnati al condominio.
        $caretakers = User::factory()->caretaker()->count(2)->create();
        $caretakers[0]->update(['name' => 'Roberto Sanna', 'email' => 'custode1@condoflow.test', 'password' => bcrypt('password')]);
        $caretakers[1]->update(['name' => 'Antonio Greco', 'email' => 'custode2@condoflow.test', 'password' => bcrypt('password')]);
        $condominium->caretakers()->attach($caretakers->pluck('id'));

        // 45 colonne verticali, ciascuna con 3 unità (una per piano: T, 1, 2) = 135 unità.
        // Ogni colonna è un edificio/scala a sé: colonna N contiene le unità T/N, 1/N, 2/N.
        $units = collect();
        for ($column = 1; $column <= 45; $column++) {
            $building = Building::create([
                'condominium_id' => $condominium->id,
                'name' => "Colonna {$column}",
                'code' => (string) $column,
                'floors_count' => 3,
            ]);

            foreach (['T', '1', '2'] as $floorCode) {
                $units->push(Unit::create([
                    'condominium_id' => $condominium->id,
                    'building_id' => $building->id,
                    'code' => "{$floorCode}/{$column}",
                    'floor' => $floorCode,
                    'type' => UnitType::Apartment,
                    'surface_sqm' => fake()->randomFloat(2, 55, 130),
                ]));
            }
        }

        // 50 condomini demo, assegnati a unità (alcuni con un secondo intestatario/inquilino).
        $residents = User::factory()->condomino()->count(50)->create()->values();
        foreach ($residents as $i => $resident) {
            $resident->update([
                'email' => "condomino{$i}@condoflow.test",
                'password' => bcrypt('password'),
            ]);
        }

        $shuffledUnits = $units->shuffle()->values();
        foreach ($residents as $i => $resident) {
            $unit = $shuffledUnits[$i];
            $unit->users()->attach($resident->id, ['relationship' => 'owner', 'is_primary' => true]);
        }
        // Qualche unità con un secondo occupante (inquilino) tra i residenti già creati.
        foreach ($residents->random(10) as $resident) {
            $unit = $shuffledUnits->random();
            if (! $unit->users()->where('users.id', $resident->id)->exists()) {
                $unit->users()->attach($resident->id, ['relationship' => 'tenant', 'is_primary' => false]);
            }
        }

        // 10 fornitori, associati al condominio.
        $suppliers = Supplier::factory()->count(10)->create(['administrator_id' => $admin->id]);
        $condominium->suppliers()->attach($suppliers->pluck('id'));

        $this->seedTickets($condominium, $units, $residents, $caretakers, $suppliers);
        $this->seedAnnouncements($condominium, $admin, $units);
        $this->seedDocuments($condominium, $admin);

        return $condominium;
    }

    private function seedTickets($condominium, $units, $residents, $caretakers, $suppliers): void
    {
        $statusWeights = [
            TicketStatus::New,
            TicketStatus::New,
            TicketStatus::TakenInCharge,
            TicketStatus::TakenInCharge,
            TicketStatus::InProgress,
            TicketStatus::InProgress,
            TicketStatus::WaitingSupplier,
            TicketStatus::Resolved,
            TicketStatus::Resolved,
            TicketStatus::Closed,
        ];

        for ($i = 0; $i < 40; $i++) {
            $unit = $units->random();
            $reporter = $unit->users()->inRandomOrder()->first() ?? $residents->random();
            $status = fake()->randomElement($statusWeights);
            $category = fake()->randomElement($this->ticketCategories);
            $priority = fake()->randomElement(TicketPriority::cases());
            $createdAt = fake()->dateTimeBetween('-60 days', '-1 days');

            $ticket = Ticket::create([
                'condominium_id' => $condominium->id,
                'unit_id' => $unit->id,
                'ticket_category_id' => $category->id,
                'created_by' => $reporter->id,
                'title' => $this->ticketTitleFor($category->name),
                'description' => fake()->realText(180),
                'priority' => $priority,
                'status' => TicketStatus::New,
                'location' => fake()->optional()->randomElement(['Cortile', 'Parcheggio', 'Corridoio piano '.$unit->floor, 'Colonna '.$unit->building?->code]),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'from_status' => null,
                'to_status' => TicketStatus::New->value,
                'changed_by' => $reporter->id,
                'created_at' => $createdAt,
            ]);

            $this->progressTicketTo($ticket, $status, $caretakers, $suppliers, $createdAt);

            foreach (range(1, fake()->numberBetween(0, 3)) as $__) {
                TicketComment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => fake()->randomElement([$reporter, ...$caretakers])->id,
                    'body' => fake()->sentence(10),
                    'is_internal' => fake()->boolean(20),
                    'created_at' => fake()->dateTimeBetween($createdAt, 'now'),
                ]);
            }

            if (fake()->boolean(30)) {
                $this->attachDemoPhoto($ticket, $reporter);
            }
        }
    }

    private function ticketTitleFor(string $category): string
    {
        return match ($category) {
            'Acqua' => 'Perdita acqua nel bagno',
            'Elettricità' => 'Interruttore scale non funziona',
            'Ascensore' => 'Ascensore bloccato tra i piani',
            'Cancello' => 'Cancello carraio non si chiude',
            'Illuminazione' => 'Lampada scala fulminata',
            'Pulizia' => 'Pulizia scale non effettuata',
            'Giardinaggio' => 'Siepe da potare in giardino',
            'Parcheggio' => 'Posto auto occupato abusivamente',
            'Piscina' => 'Acqua piscina torbida',
            'Sicurezza' => 'Telecamera ingresso non funzionante',
            'Riscaldamento' => 'Caldaia condominiale in blocco',
            default => 'Segnalazione generica',
        };
    }

    private function progressTicketTo(Ticket $ticket, TicketStatus $target, $caretakers, $suppliers, \DateTime $createdAt): void
    {
        $cursor = TicketStatus::New;
        $path = match ($target) {
            TicketStatus::New => [],
            TicketStatus::TakenInCharge => [TicketStatus::TakenInCharge],
            TicketStatus::InProgress => [TicketStatus::TakenInCharge, TicketStatus::InProgress],
            TicketStatus::WaitingSupplier => [TicketStatus::TakenInCharge, TicketStatus::InProgress, TicketStatus::WaitingSupplier],
            TicketStatus::Resolved => [TicketStatus::TakenInCharge, TicketStatus::InProgress, TicketStatus::Resolved],
            TicketStatus::Closed => [TicketStatus::TakenInCharge, TicketStatus::InProgress, TicketStatus::Resolved, TicketStatus::Closed],
        };

        if (empty($path)) {
            return;
        }

        $caretaker = fake()->randomElement($caretakers);
        $supplier = fake()->randomElement($suppliers);
        $timestamp = clone $createdAt;

        foreach ($path as $status) {
            $timestamp = (clone $timestamp)->modify('+'.fake()->numberBetween(1, 5).' days');
            if ($timestamp > now()) {
                $timestamp = now();
            }

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'from_status' => $cursor->value,
                'to_status' => $status->value,
                'changed_by' => $caretaker->id,
                'created_at' => $timestamp,
            ]);

            $cursor = $status;
        }

        $ticket->status = $target;
        $ticket->assigned_caretaker_id = $caretaker->id;
        if (in_array($target, [TicketStatus::WaitingSupplier, TicketStatus::Resolved, TicketStatus::Closed], true)) {
            $ticket->supplier_id = $supplier->id;
        }
        if (in_array($target, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
            $ticket->resolved_at = $timestamp;
        }
        if ($target === TicketStatus::Closed) {
            $ticket->closed_at = $timestamp;
        }
        $ticket->save();
    }

    private function attachDemoPhoto(Ticket $ticket, User $uploader): void
    {
        $image = imagecreatetruecolor(480, 320);
        $bg = imagecolorallocate($image, random_int(150, 220), random_int(150, 220), random_int(150, 220));
        imagefill($image, 0, 0, $bg);
        $textColor = imagecolorallocate($image, 30, 30, 30);
        imagestring($image, 5, 20, 150, 'CondoFlow - foto demo', $textColor);

        ob_start();
        imagejpeg($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        $path = 'tickets/'.$ticket->id.'/'.Str::uuid().'.jpg';
        Storage::disk('local')->put($path, $contents);

        $ticket->attachments()->create([
            'uploaded_by' => $uploader->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'foto-segnalazione.jpg',
            'mime_type' => 'image/jpeg',
            'size' => strlen($contents),
        ]);
    }

    private function seedAnnouncements(Condominium $condominium, User $admin, $units): void
    {
        $announcements = [
            ['title' => 'Pulizia straordinaria giardini', 'priority' => AnnouncementPriority::Normal],
            ['title' => 'Chiusura piscina per manutenzione', 'priority' => AnnouncementPriority::Important],
            ['title' => 'Assemblea condominiale annuale', 'priority' => AnnouncementPriority::Urgent],
            ['title' => 'Nuovo orario apertura cancello carraio', 'priority' => AnnouncementPriority::Normal],
            ['title' => 'Interruzione idrica programmata', 'priority' => AnnouncementPriority::Important],
        ];

        foreach ($announcements as $data) {
            Announcement::create([
                'condominium_id' => $condominium->id,
                'created_by' => $admin->id,
                'title' => $data['title'],
                'content' => fake()->paragraphs(3, true),
                'priority' => $data['priority'],
                'audience' => AnnouncementAudience::All,
                'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
            ]);
        }

        $building = $units->first()->building;
        if ($building) {
            $buildingAnnouncement = Announcement::create([
                'condominium_id' => $condominium->id,
                'created_by' => $admin->id,
                'title' => "Lavori idraulici Colonna {$building->code}",
                'content' => fake()->paragraph(),
                'priority' => AnnouncementPriority::Important,
                'audience' => AnnouncementAudience::Buildings,
                'published_at' => now()->subDays(3),
            ]);
            $buildingAnnouncement->buildings()->attach($building->id);
        }
    }

    private function seedDocuments(Condominium $condominium, User $admin): void
    {
        $documents = [
            ['title' => 'Regolamento condominiale', 'category' => 'Regolamento'],
            ['title' => 'Verbale assemblea 2025', 'category' => 'Verbali'],
            ['title' => 'Polizza assicurativa globale fabbricati', 'category' => 'Assicurazione'],
            ['title' => 'Piano manutenzione ascensori', 'category' => 'Manutenzione'],
            ['title' => 'Schema impianto idrico', 'category' => 'Impianti'],
        ];

        foreach ($documents as $data) {
            $category = collect($this->documentCategories)->firstWhere('name', $data['category']);
            $path = 'documents/'.Str::uuid().'.pdf';
            $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";
            Storage::disk('local')->put($path, $pdf);

            Document::create([
                'condominium_id' => $condominium->id,
                'document_category_id' => $category->id,
                'uploaded_by' => $admin->id,
                'title' => $data['title'],
                'description' => fake()->sentence(),
                'disk' => 'local',
                'path' => $path,
                'original_name' => Str::slug($data['title']).'.pdf',
                'mime_type' => 'application/pdf',
                'size' => strlen($pdf),
                'visibility' => 'all',
                'published_at' => now()->subDays(random_int(1, 60)),
            ]);
        }
    }
}
