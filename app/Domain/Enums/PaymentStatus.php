<?php

namespace App\Domain\Enums;

enum PaymentStatus: string
{
    case Pending   = 'pending';
    case Approved  = 'approved';
    case Rejected  = 'rejected';
    case Cancelled = 'cancelled';
    case Refunded  = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Aguardando pagamento',
            self::Approved  => 'Pago',
            self::Rejected  => 'Recusado',
            self::Cancelled => 'Cancelado',
            self::Refunded  => 'Estornado',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Cancelled, self::Refunded]);
    }
}
