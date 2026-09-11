<?php

namespace App\Enums;

enum ServiceType: string
{
    case General = 'general';
    case VirtualAssistant = 'virtual_assistant';
    case Professional = 'professional';
    case Creative = 'creative';
    case Technical = 'technical';
}
