<?php

namespace App\Enums;

enum DocumentType: string
{
    case Template        = 'template';
    case UserSubmission  = 'user_submission';
    case StaffAttachment = 'staff_attachment';
}
