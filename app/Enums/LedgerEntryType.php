<?php

namespace App\Enums;

enum LedgerEntryType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case EscrowHold = 'escrow_hold';
    case EscrowRelease = 'escrow_release';
    case Fee = 'fee';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
}
