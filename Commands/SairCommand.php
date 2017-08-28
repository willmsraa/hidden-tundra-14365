<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;


/**
 * Start command
 *
 * Gets executed when a user first starts using the bot.
 */
class SairCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'sair';

    /**
     * @var string
     */
    protected $description = 'Comando sair';

    /**
     * @var string
     */
    protected $usage = '/sair';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();            // Get Message object
        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();
        $text    = 'Tentando Sair da conversa...' . PHP_EOL;
//        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());
        $this->conversation = new Conversation($user_id, $chat_id);
        if($this->conversation->exists()){
            $this->conversation->stop();
            $text    = 'Saindo da conversa...' . PHP_EOL;
        }
        else{
        //    $this->conversation->stop();
            $text    = 'Ok, nenhuma conversa aberta' . PHP_EOL;
        }

         //$data = ['text' => $text];
         $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
            'reply_markup' => Keyboard::remove(),
        ];
        return Request::sendMessage($data);
    }
}
