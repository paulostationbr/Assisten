//AtHub 2017
// Projeto desenvolvido para auxiliar os usuários em seus grupos, canais e privado.
// Ao invés de plagiar, que tal nos ajudar? é só ir no @FalaAssistenBot e querer entrar na equipe.


<?php
date_default_timezone_set('America/Bahia');
define('BOT_TOKEN', 'TOKEN DO BOT FATHER');
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
define('VIRUS_KEY', 'CHAVE DA API DO VIRUS TOTAL');
function apiRequestJson($method, $parameters) {
    $parameters['method'] = $method;
    $handle = curl_init(API_URL);
    curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_exec($handle);
    curl_close($handle);
    return true;
}
function apiRequest($method, $parameters){
    $parameters['method'] = $method;
    $handle = curl_init(API_URL);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $exe = curl_exec($handle);
    curl_close($handle);
    return $exe;
}
function request($url, $content, $type){
  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_POSTFIELDS, $content);
  curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type: '.$type));
  $exe = curl_exec($handle);
  curl_close($handle);
  return $exe;
}
function user($id){
    $servername = 'localhost';
    $username = 'USERNAME';
    $password = 'SENHA';
    $database = 'BANCO DE DADOS';
    try {
        $conn = new PDO('mysql:host=' . $servername . ';dbname=' . $database, $username, $password);
    } catch (PDOException $e) {
        return 'Conexão com o banco de dados falhou, tente novamente';
    }
    $exists = false;
    $conn2 = $conn;
    $query = $conn2->query('SELECT * FROM assisten;');
    while ($linha = $query->fetch(PDO::FETCH_ASSOC)) {
        if ($linha['id'] == $id) {
          $exists = true;
        }
    }
    if ($exists === true) {
          return 'Olá  !first !last, seja bem vindo (a). Eu sou o Assisten, o seu assistente e tenho muitas funções que eu tenho certeza que você vai adorar.';
    }
    else{
          $stmt = $conn->prepare('INSERT INTO assisten VALUE (?, ?);');
          $stmt->bindValue(1, $id);
          $stmt->bindValue(2, true);
          if ($stmt->execute()){           
            return 'Olá  !first !last, seja bem vindo (a). Eu sou o Assisten, o seu assistente e tenho muitas funções que eu tenho certeza que você vai adorar.';
          }
          else{
              return 'Desculpe houve um erro tente enviar /start novamente';
          }
    }
}
function processMessage($message) {
  $message_id = $message['message_id'];
  $chat_id = $message['chat']['id'];
  $user_id = $message['from']['id'];
  if ($message['chat']['type'] == 'private' and empty($message['processInline'])) {
    apiRequestJson('sendChatAction', array('chat_id' => $chat_id, 'action' => 'typing'));
  }
  
  if (isset($message['text'])) {
    $text = $message['text'];

    if ($message['chat']['type'] == 'private') {
      if (stripos($text, '/start') === 0) {
          $text = str_ireplace('/start ', '', $text);
          $text = base64_decode($text);
          if (stripos($text, 'rules/')===0) {
            if (file_exists($text.'.apr')) {
              $return = file_get_contents($text.'.apr');
              $return = str_ireplace('!first', $message['from']['first_name'], $return);
              $return = str_ireplace('!last', $message['from']['last_name'], $return);
              $return = str_ireplace('!username', $message['from']['username'], $return);
              
              apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => $return, 'parse_mode' => 'HTML', 'reply_to_message_id' => $message_id));
              user($chat_id);
              return true;
            }
            else{
                  $id = str_ireplace('rules/', '', $text);
                  $exe = apiRequest('getChat', array('chat_id' => $id));
                  $result = json_decode($exe, true);
                  if ($result['ok'] == true) {
                      $title = isset($result['result']['title']) ? $result['result']['title'] : 'Nome desconhecido';
                      $username = isset($result['result']['username']) ? $result['result']['username'] : '-';
                      apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Não encotramos as regras para o grupo ' . $title, 'reply_to_message_id' => $message_id));
                      return true;
                  } else {
                      apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Este grupo não existe', 'reply_to_message_id' => $message_id));
                      return true;
                  }
              }
          }
          else{
            $return = user($chat_id);
            $return = str_replace('!first', $message['from']['first_name'], $return);
            $return = str_replace('!last', $message['from']['last_name'], $return);
            
            apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => $return, 'reply_to_message_id' => $message_id));
            return true;
          }
      }
    }
    elseif(isset($message['reply_to_message'])) {
        if (stripos($text, '/advert')===0 or stripos($text, 'Assisten dê um advert nele')===0) {
          $exe = apiRequest('getChatMember', array('chat_id' => $chat_id, 'user_id' => $user_id));
          $user = json_decode($exe, true);
          if ($user['result']['status'] === 'administrator' or $user['result']['status'] === 'creator') {
            $ad = $message['reply_to_message']['from']['id'];
            if (file_exists('adverts/'.$ad.' in '.$chat_id.'.apr')){
              $num = file_get_contents('adverts/'.$ad.' in '.$chat_id.'.apr');
              $num = intval($num);
              $num++;
              file_put_contents('adverts/'.$ad.' in '.$chat_id.'.apr', $num);
              if ($num==3) {
                $exe = apiRequest('kickChatMember', array('chat_id' => $chat_id, 'user_id' => $ad, 'until_date' => time()));
                $result = json_decode($exe, true);
                if ($result['ok']==true) {
                  apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'O usuário foi banido por atingir o número máximo de advertências', 'reply_to_message_id' => $message_id));
                  return true;
                }
                else{
                  apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => $result['description'], 'reply_to_message_id' => $message_id));
                  return true;
                }
              }
              else{
                apiRequestJson('sendMessage', array('chat_id'=>$chat_id, 'text'=> 'O usuário foi advertido'."\n \n".'Total de advertências: 2 de 3','reply_to_message_id' => $message_id));
                return true;
              }
            }
            else{
              $handle = fopen('adverts/'.$ad.' in '.$chat_id.'.apr', 'a');
              fwrite($handle, 1);
              fclose($handle);
              apiRequestJson('sendMessage', array('chat_id'=>$chat_id, 'text'=> 'O usuário foi advertido'."\n \n".'Total de advertências: 1 de 3','reply_to_message_id' => $message_id));
              return true;
            }
          }
          else {
                apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Você não é um adiministrador!', 'reply_to_message_id' => $message_id));
                return true;
          }
        }
        elseif (stripos($text, '/ban')===0 or stripos($text, 'Assisten remova ele')===0) {
            $exe = apiRequest('getChatMember', array('chat_id' => $chat_id, 'user_id' => $user_id));
            $user = json_decode($exe, true);
            if ($user['result']['status'] === 'administrator' or $user['result']['status'] === 'creator') {

                $ban = $message['reply_to_message']['from']['id'];
                $exe = apiRequest('kickChatMember', array('chat_id' => $chat_id, 'user_id' => $ban, 'until_date' => time()));
                $result = json_decode($exe, true);
                if ($result['ok']==true) {
                	apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'O usuário foi banido!', 'reply_to_message_id' => $message_id));
                  return true;
                }
                else{
                	apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => $result['description'], 'reply_to_message_id' => $message_id));
                    return true;
                }
            }
            else {
                apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Você não é um adiministrador!', 'reply_to_message_id' => $message_id));
                return true;
            }
        }
        elseif (stripos($text, '/unban')===0 or stripos($text, 'Assisten libere ele')===0) {
            $exe = apiRequest('getChatMember', array('chat_id' => $chat_id, 'user_id' => $user_id));
            $user = json_decode($exe, true);
            if ($user['result']['status'] === 'administrator' or $user['result']['status'] === 'creator') {
                $ban = $message['reply_to_message']['from']['id'];
                $exe = apiRequest('unbanChatMember', array('chat_id' => $chat_id, 'user_id' => $ban));
                $result = json_decode($exe, true);
                if ($result['ok']==true) {
                	apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Privelégios normais de membros restaurados!', 'reply_to_message_id' => $message_id));
                  return true;
                }
                else{
                	apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => $result['description'], 'reply_to_message_id' => $message_id));
                  return true;
                }
            }
            else {
                apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Você não é um adiministrador!', 'reply_to_message_id' => $message_id));
                return true;
            }
        }
        elseif (stripos($text, '/pin')===0 or stripos($text, 'Assisten fixe isso')===0) {
            $exe = apiRequest('getChatMember', array('chat_id' => $chat_id, 'user_id' => $user_id));
            $user = json_decode($exe, true);
            if ($user['result']['status'] === 'administrator' or $user['result']['status'] === 'creator') {
                $message_id = $message['reply_to_message']['message_id'];
                apiRequestJson('pinChatMessage', array('chat_id' => $chat_id, 'message_id' => $message_id));
                apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Mensagem Fixada', 'reply_to_message_id' => $message_id));
                return true;
            }
            else {
                apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Você não é um adiministrador!', 'reply_to_message_id' => $message_id));
                return true;
            }
        }
        elseif (stripos($text, 'Qual o id dele Assisten')===0 or stripos($text, 'Assisten qual o id dele')===0) {
          $id = $message['reply_to_message']['from']['id'];
          apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'O id dele é '.$id, 'reply_to_message_id' => $message_id));
          return true;
      }
    }
    elseif (stripos($text, '/ban')===0) {
      $palavras = str_word_count($text);
      if ($palavras==2){
          $exe = apiRequest('getChatMember', array('chat_id' => $chat_id, 'user_id' => $user_id));
          $user = json_decode($exe, true);
          if ($user['result']['status'] === 'administrator' or $user['result']['status'] === 'creator') {

                $ban = str_replace('/ban ', '', $text);
                $exe = apiRequest('kickChatMember', array('chat_id' => $chat_id, 'user_id' => $ban, 'until_date' => time()));
                $result = json_decode($exe, true);
                if ($result['ok']==true) {
                  apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'O usuário foi banido!', 'reply_to_message_id' => $message_id));
                  return true;
                }
                else{
                  apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => $result['description'], 'reply_to_message_id' => $message_id));
                }
                return true;
            }
            else {
                apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Você não é um adiministrador!', 'reply_to_message_id' => $message_id));
                return true;
            }
      }
      else{
        apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Você não passou o id ou passou coisas de mais', 'reply_to_message_id' => $message_id));
        return true;
      }
    }
    elseif (stripos($text, '/unban')) {
      $palavras = str_word_count($text);
      if ($palavras==2) {
        $exe = apiRequest('getChatMember', array('chat_id' => $chat_id, 'user_id' => $user_id));
            $user = json_decode($exe, true);
            if ($user['result']['status'] === 'administrator' or $user['result']['status'] === 'creator') {
                $ban = str_replace('/unban ', '', $text);;
                $exe = apiRequest('unbanChatMember', array('chat_id' => $chat_id, 'user_id' => $ban));
                $result = json_decode($exe, true);
                if ($result['ok']==true) {
                  apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Privelégios normais de membros restaurados!', 'reply_to_message_id' => $message_id));
                  return true;
                }
                else{
                  apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => $result['description'], 'reply_to_message_id' => $message_id));
                  return true;
                }
            }
            else {
                apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Você não é um adiministrador!', 'reply_to_message_id' => $message_id));
                return true;
            }
      }
      else{
        apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Você não passou o id ou passou coisas de mais', 'reply_to_message_id' => $message_id));
        return true;
      }
    }
    elseif (stripos($text, '/unpin')===0 or stripos($text, 'Assisten desfixe')===0){
        $exe = apiRequest('getChatMember', array('chat_id' => $chat_id, 'user_id' => $user_id));
        $user = json_decode($exe, true);
        if ($user['result']['status']==='administrator' or $user['result']['status']==='creator'){
          apiRequestJson('unpinChatMessage', array('chat_id' => $chat_id));
          apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Mensagem Desfixada', 'reply_to_message_id' => $message_id));
          return true;
        }
        else{
          $message_id = $message['reply_to_message']['message_id'];
          apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Você não é um adiministrador!', 'reply_to_message_id' => $message_id));
          return true;
        }
    }
    elseif (stripos($text, '/regras')===0 or stripos($text, 'Assisten as regras')===0){
        $start = base64_encode('rules/'.$chat_id);
        $return = '<b>Clique no botão abaixo para vê as nossas regras</b>';
        apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => $return, 'reply_to_message_id' => $message_id, 'parse_mode' => 'HTML', 'reply_markup' => array('inline_keyboard' => array(array(array('text' => 'Lê as regras', 'url' => 'https://t.me/assistenBot?start='.$start))))));
        return true;
    }
    elseif (stripos($text, '/setregras')===0){
      $exe = apiRequest('getChatMember', array('chat_id' => $chat_id, 'user_id' => $user_id));
      $user = json_decode($exe, true);
      if ($user['result']['status'] === 'administrator' or $user['result']['status'] === 'creator') {
        $text = str_ireplace('/setregras ', '', $text);
        fopen('rules/'.$chat_id.'.apr', 'a');
        file_put_contents('rules/'.$chat_id.'.apr', $text);
        apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Regras salvas', 'reply_to_message_id' => $message_id));
        return true;
      }
      else{
        apiRequestJson('sendMessage', array('chat_id'=>$chat_id, 'text' => 'Você não é um administrator!', 'reply_to_message_id'=>$message_id));
        return true;
      }
    }
    elseif (stripos($text, '/link')===0){
        if (file_exists('link/'.$chat_id.'.apr')){
          $text = file_get_contents('link/'.$chat_id.'.apr', $text);
          apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => $text, 'reply_to_message_id' => $message_id));
          return true;
        }
        else{
          apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Nada foi definido ainda', 'reply_to_message_id' => $message_id));
          return true;
        }
    }
    elseif (stripos($text, '/setlink')===0){
      $exe = apiRequest('getChatMember', array('chat_id' => $chat_id, 'user_id' => $user_id));
      $user = json_decode($exe, true);
      if ($user['result']['status'] === 'administrator' or $user['result']['status'] === 'creator') {
        $text = str_ireplace('/setlink ', '', $text);
        fopen('link/'.$chat_id.'.apr', 'a');
        file_put_contents('link/'.$chat_id.'.apr', $text);
        apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Link salvo', 'reply_to_message_id' => $message_id));
        return true;
      }
      else{
        apiRequestJson('sendMessage', array('chat_id'=>$chat_id, 'text' => 'Você não é um administrator!', 'reply_to_message_id'=>$message_id));
          return true;
      }
    }
    elseif (stripos($text, '/bemvindo')===0){
      $exe = apiRequest('getChatMember', array('chat_id' => $chat_id, 'user_id' => $user_id));
      $user = json_decode($exe, true);
      if ($user['result']['status'] === 'administrator' or $user['result']['status'] === 'creator') {
        $text = str_ireplace('/bemvindo ', '', $text);
        fopen('welcome/'.$chat_id.'.apr', 'a');
        file_put_contents('welcome/'.$chat_id.'.apr', $text);
        apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Mensagem de boas vindas salva', 'reply_to_message_id' => $message_id));
        return true;
      }
      else{
        apiRequestJson('sendMessage', array('chat_id'=>$chat_id, 'text' => 'Você não é um administrator!', 'reply_to_message_id'=>$message_id));
          return true;
      }
    }
    elseif (stripos($text, 'Qual o meu id Assisten')===0 or stripos($text, 'Assisten qual o meu id')===0) {
      apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'O id seu id é '.$user_id, 'reply_to_message_id' => $message_id));
      return true;
    }
    elseif (stripos($text, 'Qual o id do chat Assisten')===0 or stripos($text, 'Assisten qual o id do chat')===0) {
      apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'O id do chat é '.$chat_id, 'reply_to_message_id' => $message_id));
      return true;
    }
    elseif ($message['chat']['type'] == 'private'){
        apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Desculpe, não estou a compreender isso 👆', 'reply_to_message_id' => $message_id));
        return true;
    }
  }
  elseif (isset($message['new_chat_members'])) {
      
      if (file_exists('welcome/'.$chat_id.'.apr')) {
        $name['user'] = isset($message['new_chat_member']['username'])?'@'.$message['new_chat_member']['username']:'-';
        $return = file_get_contents('welcome/'.$chat_id.'.apr');
        $return = str_ireplace('!first', $message['new_chat_member']['first_name'], $return);
        $return = str_ireplace('!last', $message['new_chat_member']['last_name'], $return);
        $return = str_ireplace('!username', $name['user'], $return);
        $start = base64_encode('rules/'.$chat_id);
        apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => $return, 'parse_mode' => 'HTML', 'reply_to_message_id' => $message_id,'disable_web_page_preview' => true, 'reply_markup' => array('inline_keyboard' => array(array(array('text' => 'Lê as regras', 'url' => 'https://t.me/assistenBot?start='.$start))))));
        return true;
      }
      elseif (stripos($message['new_chat_member']['username'], 'AssistenBot')===0 and strlen($message['new_chat_member']['username'])===11) {
         apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' => 'Olá eu sou um bot que foi feito para tornar seu grupo melhor e mais intuitivo conheça mais sobre o que eu posso fazer no meu canal', 'reply_to_message_id' => $message_id, 'reply_markup' => array('inline_keyboard' => array(array(array('text' => 'Vê meu canal', 'url' => 'https://t.me/joinchat/AAAAAEJ7pWKZ3MBI9uOhjw'))))));
      }
      return true;
  }
  elseif (isset($message['left_chat_member'])) {;
      apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' =>'Adeus '.$message['left_chat_member']['first_name']. '👋 ...', 'reply_to_message_id' => $message_id));
      return true;
  }
  elseif ($message['chat']['type'] == 'private'){
      apiRequestJson('sendMessage', array('chat_id' => $chat_id, 'text' =>'Mande apenas mensagens de texto.', 'reply_to_message_id' => $message_id));
      return true;
  }
  return true;
}

//banner inline
function processInline($query){
    $q_id = $query['inline_query']['id'];
    $q_text = $query['inline_query']['query'];
    if ($q_text==='Banner Assisten'){
      $array = array('type' => "photo", "id" => base64_encode($query["update_id"]), "title" => 'DevRaiz', "description" => "Divulgação DevRaiz.", "photo_width" => 1024, "photo_height" => 1024, "thumb_url" => "http://telegra.ph/file/5a8a54ad9863d92f3ad00.jpg", "photo_url" => "http://telegra.ph/file/5a8a54ad9863d92f3ad00.jpg", "caption" => "Sou um robô feito para ser seu assistente. Fui feito para administrar canais e grupos e ter funções no privado. Ainda estou em beta.", "reply_markup" => array('inline_keyboard' => array(array(array('text' => ' 🤖 Assisten', 'url' => 'http://t.me/AssistenBot')), array(array('text' => 'Compartilhar', 'switch_inline_query' => 'Banner Assisten')))));
     apiRequestJson("answerInlineQuery", array("inline_query_id" => $q_id,  'results' => array($array)));
  }
}

$content = file_get_contents('php://input');
$update = json_decode($content, true);
if (isset($update['message'])) {
  processMessage($update['message']);
}
elseif (isset($update['inline_query'])) {
  processInline($update);
}
