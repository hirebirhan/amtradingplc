<?php

namespace App\Enums;

enum BranchCode: string
{
    case BICHA_FOK = 'BR-BFOK';
    case MERKATO = 'BR-MERC';
    case FURI = 'BR-FURI';

    public function getName(): string
    {
        return match($this) {
            self::BICHA_FOK => 'Bicha Fok Branch',
            self::MERKATO => 'Merkato Branch',
            self::FURI => 'Furi Branch',
        };
    }

    public function getManagerEmail(): string
    {
        return match($this) {
            self::BICHA_FOK => 'branch-manager-1@amtradingplc.com',
            self::MERKATO => 'branch-manager-2@amtradingplc.com',
            self::FURI => 'branch-manager-3@amtradingplc.com',
        };
    }
}