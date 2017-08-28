<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
//use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Dados;

class VtrCommand extends UserCommand{
    protected $name = 'vtr';                      // Your command's name
    protected $description = 'Viatura'; // Your command description
    protected $usage = '/vtr';                    // Usage of your command
    protected $version = '1.0.0';                  // Version of your command
    protected $need_mysql = true;
    protected $private_only = true;
    protected $conversation;
    
    public function execute(){
        $message = $this->getMessage();            // Get Message object
        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        $data = [                                  // Set up the new message data
            'chat_id' => $chat_id,                 // Set Chat ID to send the message to
            'text'    => 'Dados da Viatura:', // Set message to send
        ];
        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            //reply to message id is applied by default
            //Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }
//        return Request::sendMessage($data);        // Send message!

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }
        //$this->conversation->cancel();
        $result = Request::emptyResponse();
        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 0;
                    $this->conversation->update();
                    $data['text'] = 'Prefixo da Viatura:';
                    if ($text !== '') {
                        $data['text'] = 'Digite o Prefixo, apenas números:';
                    }
                    $result = Request::sendMessage($data);
                    break;
                }
                $notes['vtr'] = $text;
                $text = '';
            case 1:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 1;
                    $this->conversation->update();
                    $data['text'] = 'Placa da Viatura:';
                    if ($text !== '') {
                        $data['text'] = 'Digite a Placa, apenas números:';
                    }
                    $result = Request::sendMessage($data);
                    break;
                }
                $notes['placa'] = $text;
                $text = '';
            case 2:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 7;
                    $this->conversation->update();
                    $data['text'] = 'Alfa 3:';
                    if ($text !== '') {
                        $data['text'] = 'Digite o Alfa 3, apenas números:';
                    }
                    $result = Request::sendMessage($data);
                    break;
                }
                $notes['a3'] = $text;
                $text         = '';
            // case 3:
            // case 4:
            // case 5:
            // case 6:
            // case 7:
            // case 8:
            // case 9:
        }
        $data = [
            'chat_id'      => $chat_id,
            'text'         => $text,
            'reply_markup' => Keyboard::remove(),
        ];
        $result = Request::sendMessage($data);
        return $result;
    }
}