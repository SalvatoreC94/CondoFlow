<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case TakenInCharge = 'taken_in_charge';
    case InProgress = 'in_progress';
    case WaitingSupplier = 'waiting_supplier';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Segnalazione ricevuta',
            self::TakenInCharge => 'Presa in carico',
            self::InProgress => 'In lavorazione',
            self::WaitingSupplier => 'In attesa fornitore',
            self::Resolved => 'Risolta',
            self::Closed => 'Chiusa',
        };
    }

    /**
     * Allowed forward/backward transitions for the ticket status machine.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::TakenInCharge, self::InProgress, self::Closed],
            self::TakenInCharge => [self::InProgress, self::WaitingSupplier, self::Resolved, self::Closed],
            self::InProgress => [self::WaitingSupplier, self::Resolved, self::Closed],
            self::WaitingSupplier => [self::InProgress, self::Resolved, self::Closed],
            self::Resolved => [self::Closed, self::InProgress],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
