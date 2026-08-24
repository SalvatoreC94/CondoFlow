<?php

namespace App\Http\Controllers\Api;

use App\Enums\AnnouncementAudience;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Condominium;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Notification;

class AnnouncementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $request->validate(['condominium_id' => ['required', 'integer', 'exists:condominiums,id']]);

        $condominium = Condominium::findOrFail($request->integer('condominium_id'));
        $this->authorize('view', $condominium);

        $query = $condominium->announcements()->with(['author', 'buildings'])->whereNotNull('published_at');

        if ($user->role === UserRole::Condomino) {
            $query->where(function ($q) use ($user) {
                $q->where('audience', AnnouncementAudience::All->value)
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('audience', AnnouncementAudience::Buildings->value)
                            ->whereHas('buildings', fn ($b) => $b->whereIn('buildings.id', $user->units()->pluck('units.building_id')));
                    })
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('audience', AnnouncementAudience::Users->value)
                            ->whereHas('recipients', fn ($r) => $r->where('users.id', $user->id));
                    });
            });
        }

        $announcements = $query->orderByDesc('published_at')->paginate($request->integer('per_page', 20));

        return AnnouncementResource::collection($announcements);
    }

    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $condominium = Condominium::findOrFail($request->validated('condominium_id'));
        $data = $request->safe()->except(['condominium_id', 'building_ids', 'user_ids']);

        $announcement = $condominium->announcements()->create([
            ...$data,
            'created_by' => $request->user()->id,
            'published_at' => $data['published_at'] ?? now(),
        ]);

        $audience = AnnouncementAudience::from($request->validated('audience'));
        if ($audience === AnnouncementAudience::Buildings) {
            $announcement->buildings()->sync($request->validated('building_ids', []));
        } elseif ($audience === AnnouncementAudience::Users) {
            $announcement->recipients()->sync($request->validated('user_ids', []));
        }

        $this->notifyRecipients($announcement, $condominium);

        AuditLog::record('announcement.created', $announcement, [], $request->validated(), $condominium->id);

        return response()->json([
            'data' => new AnnouncementResource($announcement->load(['author', 'buildings'])),
        ], 201);
    }

    public function show(Request $request, Announcement $announcement): JsonResponse
    {
        $this->authorize('view', $announcement);

        return response()->json(['data' => new AnnouncementResource($announcement->load(['author', 'buildings']))]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        $announcement->update($request->validated());

        return response()->json(['data' => new AnnouncementResource($announcement->fresh(['author', 'buildings']))]);
    }

    public function destroy(Request $request, Announcement $announcement): JsonResponse
    {
        $this->authorize('delete', $announcement);

        $announcement->delete();

        return response()->json(null, 204);
    }

    public function markRead(Request $request, Announcement $announcement): JsonResponse
    {
        $this->authorize('view', $announcement);

        $announcement->reads()->syncWithoutDetaching([$request->user()->id => ['read_at' => now()]]);

        return response()->json(['message' => 'Segnata come letta.']);
    }

    private function notifyRecipients(Announcement $announcement, Condominium $condominium): void
    {
        $recipients = match ($announcement->audience) {
            AnnouncementAudience::All => User::whereHas('units', fn ($q) => $q->where('units.condominium_id', $condominium->id))->get(),
            AnnouncementAudience::Buildings => User::whereHas('units', fn ($q) => $q->whereIn('units.building_id', $announcement->buildings()->pluck('buildings.id')))->get(),
            AnnouncementAudience::Users => $announcement->recipients()->get(),
        };

        Notification::send($recipients, new AnnouncementPublished($announcement));
    }
}
