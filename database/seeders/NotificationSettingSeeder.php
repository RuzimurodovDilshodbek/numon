<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationSetting;

class NotificationSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'name' => 'lesson_reminder_60',
                'label' => '1 soat oldin eslatma',
                'description' => 'Dars boshlanishidan 60 daqiqa oldin yuboriladi',
                'minutes_before' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'lesson_reminder_30',
                'label' => '30 daqiqa oldin eslatma',
                'description' => 'Dars boshlanishidan 30 daqiqa oldin yuboriladi',
                'minutes_before' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'lesson_reminder_0',
                'label' => 'Dars boshida eslatma',
                'description' => 'Dars boshlanish vaqtida yuboriladi',
                'minutes_before' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($settings as $setting) {
            NotificationSetting::firstOrCreate(['name' => $setting['name']], $setting);
        }
    }
}
