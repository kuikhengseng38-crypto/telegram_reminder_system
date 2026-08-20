<?php
/**
 * Quick reminder templates.
 */

declare(strict_types=1);

function reminder_templates(): array
{
    return [
        'electricity' => [
            'name' => 'Electricity Bill',
            'icon' => 'bi-lightning-charge',
            'color' => 'amber',
            'title' => 'Electricity Bill Reminder',
            'messages' => [
                'Hello, this is a reminder that your electricity bill is due.',
                'Please make the payment before the due date to avoid disconnection.',
                'If you have already paid, you can ignore this message. Thank you.',
            ],
        ],
        'water' => [
            'name' => 'Water Bill',
            'icon' => 'bi-droplet',
            'color' => 'blue',
            'title' => 'Water Bill Reminder',
            'messages' => [
                'Hello, this is a reminder that your water bill is due.',
                'Please complete the payment as soon as possible to avoid service interruption.',
                'Thank you for your attention.',
            ],
        ],
        'internet' => [
            'name' => 'Internet Bill',
            'icon' => 'bi-wifi',
            'color' => 'violet',
            'title' => 'Internet Bill Reminder',
            'messages' => [
                'Hello, your internet / Wi-Fi bill is due.',
                'Please settle the payment to keep your connection active.',
                'Thank you.',
            ],
        ],
        'rent' => [
            'name' => 'Rent Reminder',
            'icon' => 'bi-house',
            'color' => 'teal',
            'title' => 'Rent Payment Reminder',
            'messages' => [
                'Hello, this is a reminder that your rent is due.',
                'Please make the payment by the due date.',
                'Contact us if you need any assistance. Thank you.',
            ],
        ],
        'parking' => [
            'name' => 'Parking Fee',
            'icon' => 'bi-p-square',
            'color' => 'green',
            'title' => 'Parking Fee Reminder',
            'messages' => [
                'Hello, this is a reminder that your parking fee is due.',
                'Please complete the payment to avoid extra charges.',
                'Thank you.',
            ],
        ],
        'overdue' => [
            'name' => 'Overdue Payment',
            'icon' => 'bi-exclamation-triangle',
            'color' => 'red',
            'title' => 'Overdue Payment Reminder',
            'messages' => [
                'Hello, our records show an outstanding payment.',
                'Please settle the amount as soon as possible to avoid further action.',
                'If you have already paid, please ignore this message.',
            ],
        ],
        'meeting' => [
            'name' => 'Meeting',
            'icon' => 'bi-calendar-event',
            'color' => 'blue',
            'title' => 'Meeting Reminder',
            'messages' => [
                'Hello, this is a reminder about your upcoming meeting.',
                'Please be ready on time and join as scheduled.',
                'Thank you.',
            ],
        ],
        'task' => [
            'name' => 'Task Due',
            'icon' => 'bi-check2-square',
            'color' => 'teal',
            'title' => 'Task Due Reminder',
            'messages' => [
                'Good day. This is a reminder that your task is due today.',
                'Please complete it before the deadline.',
                'Thank you.',
            ],
        ],
    ];
}

function reminder_template(string $key): ?array
{
    $all = reminder_templates();
    return $all[$key] ?? null;
}
