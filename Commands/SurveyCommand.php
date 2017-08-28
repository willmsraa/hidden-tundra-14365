<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Request;

/**
 * User "/survey" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class SurveyCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'survey';

    /**
     * @var string
     */
    protected $description = 'Survery for bot users';

    /**
     * @var string
     */
    protected $usage = '/survey';

    /**
     * @var string
     */
    protected $version = '0.3.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Conversation Object
     *
     * @var \Longman\TelegramBot\Conversation
     */
    protected $conversation;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();

        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        //Preparing Response
        $data = [
            'chat_id' => $chat_id,
        ];

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            //reply to message id is applied by default
            //Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        //Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }

        $result = Request::emptyResponse();

        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
                if ($text === '') {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text']         = 'Digite o nome:';
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['name'] = $text;
                $text          = '';

            // no break
            case 1:
                if ($text === '') {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['text'] = 'Digite o Sobrenome:';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['surname'] = $text;
                $text             = '';

            // no break
            case 2:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data['text'] = 'Digite a idade:';
                    if ($text !== '') {
                        $data['text'] = 'Digite a idade, tem que ser um número:';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['age'] = $text;
                $text         = '';

            // no break
            case 3:
                if ($text === '' || !in_array($text, ['M', 'F'], true)) {
                    $notes['state'] = 3;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard(['M', 'F']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Selecione seu gênero:';
                    if ($text !== '') {
                        $data['text'] = 'Selecione seu gênero, escolha uma opção:';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['gender'] = $text;

            // no break
            case 4:
                if ($message->getLocation() === null) {
                    $notes['state'] = 4;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard(
                        (new KeyboardButton('Localização'))->setRequestLocation(true)
                    ))
                        ->setOneTimeKeyboard(true)
                        ->setResizeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Sua Localização:';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['longitude'] = $message->getLocation()->getLongitude();
                $notes['latitude']  = $message->getLocation()->getLatitude();

            // no break
            case 5:
                if ($message->getPhoto() === null) {
                    $notes['state'] = 5;
                    $this->conversation->update();

                    $data['text'] = 'Insira uma foto:';

                    $result = Request::sendMessage($data);
                    break;
                }

                /** @var PhotoSize $photo */
                $photo             = $message->getPhoto()[0];
                $notes['photo_id'] = $photo->getFileId();

            // no break
            case 6:
                if ($message->getContact() === null) {
                    $notes['state'] = 6;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard(
                        (new KeyboardButton('Contato'))->setRequestContact(true)
                    ))
                        ->setOneTimeKeyboard(true)
                        ->setResizeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Teu contato:';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['phone_number'] = $message->getContact()->getPhoneNumber();

            // no break
            case 7:
                $this->conversation->update();
                $out_text = '/Survey resultado:' . PHP_EOL;
                unset($notes['state']);
                foreach ($notes as $k => $v) {
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $v;
                }

                $data['photo']        = $notes['photo_id'];
                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                $data['caption']      = $out_text;
                $this->conversation->stop();

                $result = Request::sendPhoto($data);
                break;
        }

        return $result;
    }
}
