<?php

namespace App\Conversations;

use App\Models\TelegramUser;
use BotMan\BotMan\Facades\BotMan;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Inspiring;


class Tsks extends Conversation
{
    protected string $id;
    protected string $key;
    /**
     * Start the conversation.
     *
     * @return mixed
     */

    public function askID() {
        $this->say('Давайте подключим Ваш аккаунт к боту');
        $question = Question::create('Введите ID пользователя, его можно найти в настройках аккаунта - API.');
        return $this->ask($question, function (Answer $answer) {
            $this->id = $answer->getText();
            $this->askKey();
        });
    }

    public function askKey() {
        return $this->ask('Введите токен пользователя. В боте он хранится в зашифрованном виде.', function (Answer $answer) {
            $this->key = $answer->getText();
            $this->checkCreds();
        });
    }
    public function run()
    {
        $this->checkUser();
    }

    public function checkCreds()
    {
        try {
            $response = new Client();
            $res = $response->request('get', 'https://habitica.com/api/v3/user', [
                'headers' => [
                    'x-client' => config('app.header'),
                    'x-api-user' => $this->id,
                    'x-api-key' => $this->key
                ]
            ]);
            if ($res) {
                $resArray = \GuzzleHttp\json_decode($res->getBody(), true);
                if ($resArray['success'] === true) {
                    $telegramUser = new TelegramUser([
                        'telegram_id' => $this->getBot()->getUser()->getId(),
                        'habitica_id' => $this->id,
                        'habitica_key' => encrypt($this->key, false)
                    ]);
                    $telegramUser->save();
                    return $this->say('Успешно для ' . $resArray['data']['auth']['local']['username'] . '. Теперь вы можете отправлять задачи');
                } else {

                    return $this->say('Неверные данные. Попробуете еще раз?');
                }
            }
        } catch (GuzzleException $e) {
            if ($e->getCode() === 401) {
                $question = Question::create('Неверные данные. Попробуете еще раз?')->addButtons([
                    Button::create('Да')->value('yes'),
                    Button::create('Нет')->value('no'),
                ]);
                return $this->ask($question, function (Answer $answer) {
                    if ($answer->isInteractiveMessageReply()) {
                        if ($answer->getValue() === 'yes') {
                            $this->run();

                        } else {
                            $this->say('Действие отменено');
                        }
                    }
                });

            }
            return $this->say($e->getMessage());
        }
    }
    public function checkUser() {
        try {
            $user = TelegramUser::where(['telegram_id' => $this->bot->getUser()->getId()])->firstOrFail();
            $user->delete();
            $this->say('Бот перезапущен');
            $this->askID();
        } catch (ModelNotFoundException $e) {
            $this->askID();
        }
    }
}
