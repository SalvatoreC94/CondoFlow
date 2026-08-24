<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\Ticket;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, [UserRole::Administrator, UserRole::Caretaker], true), 403);

        $accessibleIds = $user->role === UserRole::Administrator
            ? $user->administeredCondominiums()->pluck('id')
            : $user->assignedCondominiums()->pluck('condominiums.id');

        if ($request->filled('condominium_id')) {
            $condominiumId = $request->integer('condominium_id');
            abort_unless($accessibleIds->contains($condominiumId), 403);
            $condominiumIds = collect([$condominiumId]);
        } else {
            $condominiumIds = $accessibleIds;
        }

        $ticketsQuery = Ticket::whereIn('condominium_id', $condominiumIds)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')));

        $openStatuses = [
            TicketStatus::New->value,
            TicketStatus::TakenInCharge->value,
            TicketStatus::InProgress->value,
            TicketStatus::WaitingSupplier->value,
        ];

        $resolvedTickets = (clone $ticketsQuery)->whereIn('status', [TicketStatus::Resolved->value, TicketStatus::Closed->value])
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at']);

        $avgResolutionHours = $resolvedTickets->isEmpty()
            ? null
            : round($resolvedTickets->avg(fn ($t) => $t->created_at->diffInHours($t->resolved_at)), 1);

        $suppliersToFollowUp = (clone $ticketsQuery)
            ->where('status', TicketStatus::WaitingSupplier->value)
            ->whereNotNull('supplier_id')
            ->with('supplier')
            ->get()
            ->groupBy('supplier_id')
            ->map(fn ($tickets) => [
                'supplier' => $tickets->first()->supplier?->only(['id', 'name', 'phone', 'email']),
                'tickets_waiting' => $tickets->count(),
                'oldest_waiting_since' => $tickets->min('updated_at'),
            ])
            ->values();

        $announcements = Announcement::whereIn('condominium_id', $condominiumIds)
            ->whereNotNull('published_at')
            ->with('author')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'condominiums_count' => $condominiumIds->count(),
                'units_count' => Unit::whereIn('condominium_id', $condominiumIds)->count(),
                'tickets_open' => (clone $ticketsQuery)->whereIn('status', $openStatuses)->count(),
                'tickets_urgent' => (clone $ticketsQuery)->where('priority', TicketPriority::Urgent->value)->whereIn('status', $openStatuses)->count(),
                'tickets_waiting_supplier' => (clone $ticketsQuery)->where('status', TicketStatus::WaitingSupplier->value)->count(),
                'tickets_resolved' => (clone $ticketsQuery)->whereIn('status', [TicketStatus::Resolved->value, TicketStatus::Closed->value])->count(),
                'avg_resolution_hours' => $avgResolutionHours,
                'tickets_by_status' => (clone $ticketsQuery)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
                'tickets_by_priority' => (clone $ticketsQuery)->select('priority', DB::raw('count(*) as total'))->groupBy('priority')->pluck('total', 'priority'),
                'suppliers_to_follow_up' => $suppliersToFollowUp,
                'recent_announcements' => AnnouncementResource::collection($announcements),
            ],
        ]);
    }
}
