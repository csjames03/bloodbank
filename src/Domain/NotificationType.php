<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Kinds of in-app notification, with how each one is presented.
 */
enum NotificationType: string
{
    // To blood bank staff
    case RequestCreated = 'request_created';
    case RequestEmergency = 'request_emergency';
    case RequestWithdrawn = 'request_withdrawn';
    case RequestReceived = 'request_received';
    case DonationLogged = 'donation_logged';
    case StockLow = 'stock_low';
    case StockOut = 'stock_out';
    case UnitsExpiring = 'units_expiring';
    case UnitsExpired = 'units_expired';

    // To hospitals
    case RequestFiled = 'request_filed';
    case RequestDispatched = 'request_dispatched';
    case RequestDelivered = 'request_delivered';
    case RequestCancelled = 'request_cancelled';

    public function icon(): string
    {
        return match ($this) {
            self::RequestCreated, self::RequestFiled => 'clipboard',
            self::RequestDispatched => 'truck',
            self::RequestDelivered, self::RequestReceived => 'check',
            self::RequestCancelled, self::RequestWithdrawn => 'ban',
            self::DonationLogged => 'heart',
            self::StockLow, self::StockOut, self::RequestEmergency => 'alert',
            self::UnitsExpiring => 'clock',
            self::UnitsExpired => 'trash',
        };
    }

    /** Colour tone, matching the badge/stat tones in the stylesheet. */
    public function tone(): string
    {
        return match ($this) {
            self::RequestCreated, self::RequestFiled => 'info',
            self::RequestDispatched => 'primary',
            self::RequestDelivered, self::RequestReceived, self::DonationLogged => 'success',
            self::RequestCancelled, self::RequestWithdrawn => 'neutral',
            self::StockLow, self::UnitsExpiring => 'warning',
            self::StockOut, self::RequestEmergency, self::UnitsExpired => 'danger',
        };
    }

    /** Stored types that are no longer known still render, as plain info. */
    public static function present(string $value): self
    {
        return self::tryFrom($value) ?? self::RequestCreated;
    }
}
