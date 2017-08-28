<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
//use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Dados;

class EfetivoCommand extends UserCommand{
    protected $name = 'efetivo';                      // Your command's name
    protected $description = 'Efetivo'; // Your command description
    protected $usage = '/efetivo';                    // Usage of your command
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
            'text'    => 'Dados do Serviço:', // Set message to send
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
                if ($text === '' || !in_array($text, ['Anguera', 'Antônio Cardoso', 
                                            'Ipecaetá', 'Rafael Jambeiro', 'Santo Estevão', 'Serra Preta', 'Tanquinho'], true)) {
                    $notes['state'] = 0;
                    $this->conversation->update();
                    $data['reply_markup'] = (new Keyboard(['Anguera', 'Antônio Cardoso', 
                                            'Ipecaetá', 'Rafael Jambeiro', 'Santo Estevão', 'Serra Preta', 'Tanquinho']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);
                    $data['text'] = 'Local do Serviço:';
                    if ($text !== '') {
                        $data['text'] = 'Selecione uma Localidade';
                    }
                    
                    $result = Request::sendMessage($data);
                    //$this->conversation->update();
                    break;
                }
                switch($text){
                    case 'Anguera':
                        $text = 5;
                        break;
                    case 'Antônio Cardoso':
                        $text = 3;
                        break;
                    case 'Ipecaetá':
                        $text = 2;
                        break;
                    case 'Rafael Jambeiro':
                        $text = 4;
                        break;
                    case 'Santo Estevão':
                        $text = 1;
                        break;
                    case 'Serra Preta':
                        $text = 6;
                        break;
                    case 'Tanquinho':
                        $text = 8;
                        break;
                }
                $notes['cidade'] = $text;
//                $notes['ncidade'] = $text;
//                $cdd = '';
                $text = '';
            case 1:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 1;
                    $this->conversation->update();
                    $data['text'] = 'Matrícula do comandante:';
                    if ($text !== '') {
                        $data['text'] = 'Digite a matrícula, apenas números:';
                    }
                    $result = Request::sendMessage($data);
                    break;
                }
                $notes['cmdt'] = $text;
                $text         = '';
                // if(sematricula($text)){
                //     $notes['cmdt'] = sematricula($text);
                //     $text         = '';
                // }
                // else{
                //     $data['text'] = 'Digite uma matrícula válida!';
                // }
            case 2:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 2;
                    $this->conversation->update();
                    $data['text'] = 'Matrícula do motorista:';
                    if ($text !== '') {
                        $data['text'] = 'Digite a matrícula, apenas números:';
                    }
                    $result = Request::sendMessage($data);
                    break;
                }
                $notes['mot'] = $text;
                $text         = '';
            case 3:
                if ($text === 0 || $text === '0'){
                    $notes['state'] = 6;
                    //$text         = '0';
                    //break;
                }
                else{
                    if($text === '' || !is_numeric($text)) {
                        $notes['state'] = 3;
                        $this->conversation->update();
                        $data['text'] = 'Matrícula do 1º Patrulheiro:';
                        if ($text !== '') {
                            $data['text'] = 'Digite a matrícula, apenas números:';
                        }
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $notes['pat1'] = $text;
                    $text         = '';
                }
            case 4:
                if ($text === 0 || $text === '0'){
                    $notes['state'] = 6;
                    //$text         = '0';
                    //break;
                }
                else{
                    if ($text === '' || !is_numeric($text)) {
                            $notes['state'] = 4;
                            $this->conversation->update();
                            $data['text'] = 'Matrícula do 2º Patrulheiro:';
                            if ($text !== '') {
                                $data['text'] = 'Digite a matrícula, apenas números:';
                            }
                            $result = Request::sendMessage($data);
                            break;
                    }
                    $notes['pat2'] = $text;
                    $text         = '';
                }
            case 5:
                if ($text === 0 || $text === '0'){
                    $notes['state'] = 6;
                    //$text         = '0';
                    //break;
                }
                else{
                    if ($text === '' || !is_numeric($text)) {
                        $notes['state'] = 5;
                        $this->conversation->update();
                        $data['text'] = 'Matrícula do 3º Patrulheiro:';
                        if ($text !== '') {
                            $data['text'] = 'Digite a matrícula, apenas números:';
                        }
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $notes['pat3'] = $text;
                    $text         = '';
                }
            case 6:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 6;
                    $this->conversation->update();
                    $data['text'] = 'Prefixo da Viatura:';
                    if ($text !== '') {
                        $data['text'] = 'Digite o Prefixo, apenas números:';
                    }
                    $result = Request::sendMessage($data);
                    break;
                }
                if ($text === '0' || $text === 0) {
                    $notes['state'] = 6;
                    $this->conversation->update();
                    $data['text'] = 'Prefixo da Viatura:';
                    $result = Request::sendMessage($data);
                    break;
                }
                $notes['vtr'] = $text;
                $text         = '';
            case 7:
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
            case 8:
                $notes['state'] = 9;
                $this->conversation->update();
                $cidade = $notes['cidade'] ;
                switch($cidade){
                    case 1:
                        $cdd = 'Santo Estevão';
                        break;
                    case 2:
                        $cdd = 'Ipecaetá';
                        break;
                    case 3:
                        $text = 'Antônio Cardoso';
                        break;
                    case 4:
                        $cdd = 'Rafael Jambeiro';
                        break;
                    case 5:
                        $cdd = 'Anguera';
                        break;
                    case 6:
                        $cdd = 'Serra Preta';
                        break;
                    case 8:
                        $cdd = 'Tanquinho';
                        break;
                }

                $out_text = 'Confirme:';
                //unset($notes['state']);
                // foreach ($notes as $k => $v) {
                //     $out_text .= PHP_EOL . ucfirst($k) . ': ' . $v;
                // }
                $out_text .= PHP_EOL . 'Cidade: '.$cdd;
                $out_text .= PHP_EOL . 'Comandante: '.$notes['cmdt'];
                $out_text .= PHP_EOL . 'Motorista: '.$notes['mot'];
                if (isset($notes['pat1'])){
                    $out_text .= PHP_EOL . '1º Patr.: '.$notes['pat1'];
                    if (isset($notes['pat2'])){
                        $out_text .= PHP_EOL . '2º Patr.: '.$notes['pat2'];
                        if (isset($notes['pat3'])){
                            $out_text .= PHP_EOL . '1º Patr.: '.$notes['pat3'];
                        }
                    }
                }
                $out_text .= PHP_EOL . 'Viatura: '.$notes['vtr'];
                $out_text .= PHP_EOL . 'Alfa 3: '.$notes['a3'];
                $data['text'] = $out_text;
//                $result = Request::sendMessage($data);
 //               break;
            case 9:
                if ($text === '' || !in_array($text, ['Sim', 'Não'], true)) {
                    unset($notes['state']);
                    //$notes['state'] = 5;
                    //$this->conversation->update();
                    $data['reply_markup'] = (new Keyboard(['Sim', 'Não']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);
//                    $data['text'] = 'Confirme:';
                    $result = Request::sendMessage($data);
                    /*                    if ($text !== '') {
                        $data['text'] = 'Escolha uma opção:';
                    }
 */
                    break;
                }
                //$txt = 'text: '.$text;
                //$data['text'] = $text;
                //return Request::sendMessage($data);
                if ($text === 'Sim') {
                    $this->conversation->update();
                    $this->conversation->stop();
                }
                else{
                    $this->conversation->cancel();
                }

            //$this->conversation->update();
        }
        $data = [
            'chat_id'      => $chat_id,
            'text'         => $text,
            'reply_markup' => Keyboard::remove()
        ];
        $result = Request::sendMessage($data);
        return $result;
    }
}