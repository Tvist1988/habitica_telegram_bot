<?php

namespace App\Conversations;


use App\Models\TelegramUser;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Tasks extends Conversation
{
    protected TelegramUser $user;
    protected string $task;
    /**
     * Start the conversation.
     *
     * @return mixed
     */
    public function run()
    {
        $this->checkUser();
    }

    public function checkUser() {
        try {
            $this->task = $this->bot->getMessage()->getText();
            $this->user = TelegramUser::where(['telegram_id' => $this->bot->getUser()->getId()])->firstOrFail();
            $this->createTask();

        } catch (ModelNotFoundException $e) {
            $question = Question::create('Мы не нашли Вашу учетную запись Habitica. Внести данные?')->addButtons([
                Button::create('Да')->value('yes'),
                Button::create('Нет')->value('no')
            ]);
            $this->ask($question, function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() === 'yes') {
                        $this->bot->startConversation(new Tsks);
                    } else {
                        return $this->say('Действие отменено');
                    }
                }
            });
        }
    }

    public function createTask() {
         try {
             $task = [
                  'text' => $this->task,
                  'type' => 'todo',
             ];
             $response = new Client();
             $res = $response->request('post', 'https://habitica.com/api/v3/tasks/user', [
                 'headers' => [
                     'x-client' => config('app.header'),
                     'x-api-user' => $this->user->habitica_id,
                     'x-api-key' => decrypt($this->user->habitica_key, false)
                 ],
                 'json' => $task
             ]);
             return $this->say('Задача была успешно создана.');
         } catch (GuzzleException $e) {
             return $this->say($e->getMessage());
         }
    }
}
