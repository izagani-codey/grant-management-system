<?php

namespace App\Enums;

enum RequestStatus: int
{
    case SUBMITTED = 2;
    case STAFF1_REVIEWED = 3;
    case STAFF2_APPROVED = 4;
    case COMPLETED = 5;
    case RETURNED = 6;
    case DECLINED = 7;

    public function getLabel(): string
    {
        return match($this) {
            self::SUBMITTED => 'Submitted',
            self::STAFF1_REVIEWED => 'Checked by Staff 1',
            self::STAFF2_APPROVED => 'Approved by Staff 2',
            self::COMPLETED => 'Completed',
            self::RETURNED => 'Returned for Revision',
            self::DECLINED => 'Declined',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::SUBMITTED => 'bg-orange-100 text-orange-700',
            self::STAFF1_REVIEWED => 'bg-blue-100 text-blue-700',
            self::STAFF2_APPROVED => 'bg-green-100 text-green-700',
            self::COMPLETED => 'bg-teal-100 text-teal-700',
            self::RETURNED => 'bg-yellow-100 text-yellow-700',
            self::DECLINED => 'bg-red-100 text-red-700',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::DECLINED]);
    }

    public function canBeEditedByAdmission(): bool
    {
        return $this === self::RETURNED;
    }

    public function canBeResubmittedByUser(): bool
    {
        return $this === self::RETURNED;
    }

    public function canBeActionedByStaff1(): bool
    {
        return in_array($this, [self::SUBMITTED, self::STAFF2_APPROVED]);
    }

    public function canBeActionedByStaff2(): bool
    {
        return in_array($this, [self::SUBMITTED, self::STAFF1_REVIEWED]);
    }

    public static function getAllCases(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->getLabel()
        ])->toArray();
    }
}
