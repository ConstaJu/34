<?php


require_once 'simplevk-master/autoload.php'; // Подключение библиотеки SimpleVK



const VK_KEY = '*****************************************'; // Ключ доступа сообщества
const VK_USER_KEY = '*****************************************'; // Ключ доступа пользователя
const ACCESS_KEY = '*******'; // Строка, которую должен вернуть сервер
const VERSION = '5.131'; // Версия API

const BOT_ENABLED = true; // Константа, отвечающая за статус бота (вкл/выкл)
const CONF_LOG_ID = 0; // Айди беседы, в которой будут логироваться комменты (необходимо вставить своё)
const GROUP_ID = 0; // Айди вашей группы (необходимо вставить своё)

const EXCEPTION_USERS = array (
    // Через запятую можно перечислить айдишники
); // Массив с айдишниками юзеров, на чьи комментарии бот реагировать не будет



if ( BOT_ENABLED ) // Проверка на включённость бота
{



    $vk = \DigitalStar\vk_api\vk_api::create( VK_KEY, VERSION )->setConfirm( ACCESS_KEY ); // Авторизация с помощью ключа сообщества
    $vk_user = \DigitalStar\vk_api\vk_api::create( VK_USER_KEY, VERSION ); // Авторизация с помощью ключа пользователя

    $vk->initVars( $peer_id, $message, $payload, $vk_id, $type, $data ); // Инициализация переменных



    if ( $type == 'message_new' )
    {


        if ( $message == '/айди' and $peer_id > 2000000000 ) {

            $vk->sendMessage( $peer_id, $peer_id );

        }



        if ( isset( $data->object->payload ) )
            $payload = json_decode( $data->object->payload, true );
        else
            $payload = null;
        $payload = $payload['command'];


        if ( $payload !== null )
        {

            $command = explode( '_', $payload ); // Преобразование строки в массив через разделитель в виде нижнего подчёркивания '_'

            if ( $command[0] == 'del' ) // Если была нажата кнопка 'Удалить'
            {
                try
                {
                    $vk_user->request( 'wall.deleteComment', $params = [ 'owner_id' => $command[1], 'comment_id' => $command[2] ] ); // Удаление комментария
                    $vk->sendMessage( CONF_LOG_ID, 'Комментарий удалён' ); // Отправление в беседу сбщ о том, что комментарий был успешно удалён
                }
                catch ( \DigitalStar\vk_api\VkApiException $e )
                {
                    $vk->sendMessage( CONF_LOG_ID, 'Произошла ошибка. Возможно, комментарий уже удалён.' ); // Уведомление об ошибке
                }
            }
            else if ( $command[0] == 'ban' ) // Если была нажата кнопка 'Забанить'
            {
                try
                {
                    $vk_user->request( 'wall.deleteComment', $params = [ 'owner_id' => $command[1], 'comment_id' => $command[2] ] ); // Удаление комментария
                    $vk_user->request( 'groups.ban', $params = [ 'group_id' => GROUP_ID, 'owner_id' => $command[3], 'comment' => '*Причина*', 'comment_visible' => 1 ] ); // Бан пользователя,
                    $vk->sendMessage( CONF_LOG_ID, 'Пользователь заблокирован' );
                }
                catch ( \DigitalStar\vk_api\VkApiException $e )
                {
                    $vk->sendMessage( CONF_LOG_ID, 'Произошла ошибка. Возможно, пользователь уже заблокирован или его комментарий был удалён.' );
                }
            }

        }


    }
    else if ( $type == 'wall_reply_new' or $type == 'wall_reply_edit' )
    {


        if ( in_array( $data->object->from_id, EXCEPTION_USERS ) )
            exit; // Если пользователь в списке исключений, то завершаем работу скрипта

        $userInfo = $vk->userInfo( $data->object->from_id, $scope = [ 'first_name', 'last_name', 'sex' ] ); // Получаем имя, фамилию и пол юзера
        $end = $userInfo['sex'] == 1 ? 'а' : ''; // Если пол женский - добавленям окончание 'а' для глагола
        $do = $type == 'wall_reply_new' ? 'оставил' . $end . ' новый' : 'изменил' . $end . ' свой';


        $is_member = $vk->request( 'groups.isMember', $params = [ 'group_id' => GROUP_ID, 'user_id' => $userInfo['id']  ] ) ? 'Подписчик ' : ''; // Если юзер является подписчиком сообщетсва, то добавляем соответсвующую приписку к сообщению бота

        $del_btn = $vk->buttonText( 'Удалить', 'red', [ 'command' => 'del_' . $data->object->owner_id . '_' . $data->object->id ] ); // Формируем команду для кнопки 'Удалить', записываем в неё айди юзера и айди комментария
        $ban_btn = $vk->buttonText( 'Забанить', 'red', [ 'command' => 'ban_' . $data->object->owner_id . '_' . $data->object->id . '_' . $data->object->from_id ] ); // Формируем команду для кнопки 'Забанить', записываем в неё айди юзера и айди комментария

        $vk->sendButton( CONF_LOG_ID, $is_member . '@id' . $userInfo['id'] . '(' . $userInfo['first_name'] . ' ' . $userInfo['last_name'] . ')' . $do . ' комментарий', [ [ $del_btn, $ban_btn ] ], true, false, $params = [ 'attachment' => 'wall' . $data->object->owner_id . '_' . $data->object->id ] ); // Отправляем сообщение с inline-кнопками и прикрепленным комменом


    }



}
