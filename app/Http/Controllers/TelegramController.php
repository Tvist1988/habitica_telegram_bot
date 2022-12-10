<?php

namespace App\Http\Controllers;

use App\Models\TelegramUser;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\LaravelCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramContactDriver;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\Telegram\TelegramLocationDriver;
use BotMan\Drivers\Telegram\TelegramPhotoDriver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    public function index(Request $request)
    {
        DriverManager::loadDriver(TelegramDriver::class);
        DriverManager::loadDriver(TelegramPhotoDriver::class);
        DriverManager::loadDriver(TelegramLocationDriver::class);
        DriverManager::loadDriver(TelegramContactDriver::class);

        $config = array_merge(
            config('botman.config'),
            [
                'telegram' => config('botman.telegram', [])
            ]
        );

        $botman = BotManFactory::create($config, new LaravelCache);

        $botman->hears('/start', function (BotMan $bot) {
            $bot->startConversation(new \App\Conversations\Tsks);
        });
        $botman->hears('/help', function (BotMan $bot) {
            $bot->reply('Нашли ошибку в работе бота? По всем вопросам Вы можете обратиться на почту habiticabot@yandex.ru. Так же буду рад обратной связи и пожеланиям по добавлению нового функционала.');

        });
        $botman->fallback(function(BotMan $bot) {
            $bot->startConversation(new \App\Conversations\Tasks);
        });

        $botman->hears('/stop', function (BotMan $bot) {
            try {
                $user = TelegramUser::where(['telegram_id' => $bot->getUser()->getId()])->firstOrFail();
                $user->delete();
                $bot->reply('Данные удалены, бот остановлен');
            } catch (ModelNotFoundException $e) {
                $bot->reply('Данные не найдены.');
            }
        });

        $botman->listen();
    }
}
