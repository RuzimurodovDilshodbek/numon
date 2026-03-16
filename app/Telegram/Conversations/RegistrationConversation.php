<?php

namespace App\Telegram\Conversations;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use App\Models\BotRegistration;
use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\Storage;

class RegistrationConversation extends Conversation
{
    protected ?string $step = 'ask_id';
    protected array $answers = [];

    public function ask_id(Nutgram $bot): void
    {
        $bot->sendMessage(
            "Assalomu alaykum! 🎓\n\nNurCRM tizimiga xush kelibsiz!\n\n" .
            "Dars beruvchi tomonidan sizga berilgan *6 raqamli ID* ni kiriting:",
            parse_mode: 'Markdown'
        );
        $this->next('check_id');
    }

    public function check_id(Nutgram $bot): void
    {
        $input = trim($bot->message()->text ?? '');

        $student = User::where('student_id', $input)
            ->role('student')
            ->whereNull('telegram_id')
            ->first();

        if (!$student) {
            $bot->sendMessage("❌ ID topilmadi yoki allaqachon ro'yxatdan o'tilgan.\n\nQaytadan kiriting:");
            return;
        }

        $reg = BotRegistration::where('telegram_id', (string) $bot->userId())->first();
        $reg->update([
            'student_id' => $student->id,
            'step' => 'id_entered',
            'temp_data' => ['student_id' => $student->id, 'name' => $student->name],
        ]);

        $bot->sendMessage("✅ Topildi! Salom, *{$student->name}*!\n\nEndi bir necha savollarga javob bering.", parse_mode: 'Markdown');

        $this->next('ask_age');
        $this->ask_age($bot);
    }

    public function ask_age(Nutgram $bot): void
    {
        $bot->sendMessage("📅 Yoshingiz nechida?");
        $this->next('save_age');
    }

    public function save_age(Nutgram $bot): void
    {
        $age = (int) ($bot->message()->text ?? 0);
        if ($age < 5 || $age > 100) {
            $bot->sendMessage("Iltimos, to'g'ri yosh kiriting (5-100):");
            return;
        }
        $this->answers['age'] = $age;
        $this->next('ask_goal');
        $this->ask_goal($bot);
    }

    public function ask_goal(Nutgram $bot): void
    {
        $keyboard = ReplyKeyboardMarkup::make(resize_keyboard: true)
            ->addRow(KeyboardButton::make('🌍 Sayohat'), KeyboardButton::make('💼 Biznes'))
            ->addRow(KeyboardButton::make("🎓 Ta'lim"), KeyboardButton::make('💬 Muloqot'));

        $bot->sendMessage("🎯 Turk tilini o'rganish maqsadingiz?", reply_markup: $keyboard);
        $this->next('save_goal');
    }

    public function save_goal(Nutgram $bot): void
    {
        $this->answers['learning_goal'] = $bot->message()->text ?? '';
        $this->next('ask_experience');
        $this->ask_experience($bot);
    }

    public function ask_experience(Nutgram $bot): void
    {
        $keyboard = ReplyKeyboardMarkup::make(resize_keyboard: true)
            ->addRow(KeyboardButton::make('✅ Ha, tajribam bor'), KeyboardButton::make("❌ Yo'q, yangi boshlayman"));

        $bot->sendMessage("📚 Oldindan turk tili bo'yicha tajribangiz bormi?", reply_markup: $keyboard);
        $this->next('save_experience');
    }

    public function save_experience(Nutgram $bot): void
    {
        $this->answers['previous_language_experience'] = $bot->message()->text ?? '';
        $this->next('ask_preferred_time');
        $this->ask_preferred_time($bot);
    }

    public function ask_preferred_time(Nutgram $bot): void
    {
        $keyboard = ReplyKeyboardMarkup::make(resize_keyboard: true)
            ->addRow(KeyboardButton::make('🌅 Ertalab (8-12)'), KeyboardButton::make('☀️ Kunduzi (12-17)'))
            ->addRow(KeyboardButton::make('🌆 Kechqurun (17-21)'), KeyboardButton::make('⭐ Istalgan vaqt'));

        $bot->sendMessage("⏰ Dars uchun qulay vaqt?", reply_markup: $keyboard);
        $this->next('save_preferred_time');
    }

    public function save_preferred_time(Nutgram $bot): void
    {
        $this->answers['preferred_time'] = $bot->message()->text ?? '';
        $this->next('ask_photo');
        $this->ask_photo($bot);
    }

    public function ask_photo(Nutgram $bot): void
    {
        $bot->sendMessage(
            "📸 Zo'r! Oxirgi qadam:\n\nKamerangizni oching va *o'zingizning rasmingizni* yuboring.\n\n" .
            "Bu tizimda sizni aniqlash uchun ishlatiladi.",
            parse_mode: 'Markdown'
        );
        $this->next('save_photo');
    }

    public function save_photo(Nutgram $bot): void
    {
        $photos = $bot->message()->photo;

        if (!$photos) {
            $bot->sendMessage("❌ Iltimos, rasm yuboring (matn emas):");
            return;
        }

        $photo = end($photos);
        $fileId = $photo->file_id;

        $file = $bot->getFile($fileId);
        $url = "https://api.telegram.org/file/bot" . config('nutgram.token') . "/{$file->file_path}";
        $content = @file_get_contents($url);
        $filename = 'students/' . $bot->userId() . '_' . time() . '.jpg';

        if ($content) {
            Storage::put($filename, $content);
        }

        $reg = BotRegistration::where('telegram_id', (string) $bot->userId())->first();
        $student = User::find($reg->student_id);

        $student->update([
            'telegram_id' => (string) $bot->userId(),
            'telegram_username' => $bot->user()->username ?? null,
            'telegram_photo_url' => $url,
        ]);

        StudentProfile::updateOrCreate(
            ['student_id' => $student->id],
            [
                'age' => $this->answers['age'] ?? null,
                'learning_goal' => $this->answers['learning_goal'] ?? null,
                'previous_language_experience' => $this->answers['previous_language_experience'] ?? null,
                'preferred_time' => $this->answers['preferred_time'] ?? null,
                'photo_path' => $filename,
                'questionnaire_answers' => $this->answers,
            ]
        );

        $reg->update(['step' => 'completed']);

        $mainKeyboard = ReplyKeyboardMarkup::make(resize_keyboard: true)
            ->addRow(KeyboardButton::make('📅 Darslarim'), KeyboardButton::make('📋 Vazifalar'))
            ->addRow(KeyboardButton::make('👤 Profilim'), KeyboardButton::make("💳 To'lovlar"));

        $bot->sendMessage(
            "🎉 Tabriklaymiz, *{$student->name}*!\n\n" .
            "Ro'yxatdan muvaffaqiyatli o'tdingiz!\n\n" .
            "Darslar haqida bildirishnomalar yuborib turamiz. Omad! 📚",
            parse_mode: 'Markdown',
            reply_markup: $mainKeyboard
        );

        $this->end();
    }
}
