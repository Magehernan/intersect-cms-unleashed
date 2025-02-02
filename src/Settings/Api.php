<?php

namespace App\Settings;

use App\Entity\CmsSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Api
{
    private $em;
    private $token;
    private $username;
    private $password;
    private $server;
    private $client;
    private $dedipass_public;
    private $dedipass_private;


    public function __construct(EntityManagerInterface $em, HttpClientInterface $client)
    {
        $this->em = $em;
        $this->token = $em->getRepository(CmsSettings::class)->findOneBy(['setting' => 'api_token'])->getDefaultValue();
        $this->username = $em->getRepository(CmsSettings::class)->findOneBy(['setting' => 'api_username'])->getDefaultValue();
        $this->password = $em->getRepository(CmsSettings::class)->findOneBy(['setting' => 'api_password'])->getDefaultValue();
        $this->server = $em->getRepository(CmsSettings::class)->findOneBy(['setting' => 'api_server'])->getDefaultValue();
        $this->client = $client;
        $this->dedipass_public = $em->getRepository(CmsSettings::class)->findOneBy(['setting' => 'credit_dedipass_public_key'])->getDefaultValue();
        $this->dedipass_private = $em->getRepository(CmsSettings::class)->findOneBy(['setting' => 'credit_dedipass_private_key'])->getDefaultValue();
    }

    /* Function API */
    public function APIcall_POST($server, $postData, $access_token, $calltype)
    {
        $ch = curl_init($server . $calltype);

        if ($postData != null) {
            curl_setopt_array($ch, array(
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 0,
                CURLOPT_TIMEOUT_MS => 0,
                CURLOPT_HTTPHEADER => array(
                    'authorization:Bearer ' . $access_token, // "authorization:Bearer", et non pas "authorization: Bearer"
                    'Content-Type:application/json' // "Content-Type:application/json", et non pas "Content-Type: application/json"
                ),
                CURLOPT_POSTFIELDS => json_encode($postData)
            ));
        } else {
            curl_setopt_array($ch, array(
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 0,
                CURLOPT_TIMEOUT_MS => 0,
                CURLOPT_HTTPHEADER => array(
                    'authorization:Bearer ' . $access_token, // "authorization:Bearer", et non pas "authorization: Bearer"
                    'Content-Type:application/json' // "Content-Type:application/json", et non pas "Content-Type: application/json"
                ),
            ));
        }

        $response = curl_exec($ch);

        if ($response === false) {
            return (curl_error($ch));
        }
        $responseData = json_decode($response, true);
        curl_close($ch);

        return $responseData;
    }

    // Permet de récupérer des données depuis la BDD via l'API : liste des joueurs, etc
    public function APIcall_GET($server, $access_token, $calltype)
    {
        // die($server.$calltype);
        $ch = curl_init($server . $calltype);
        curl_setopt_array($ch, array(
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_TIMEOUT_MS => 0,
            CURLOPT_HTTPHEADER => array(
                'authorization:Bearer ' . $access_token, // "authorization:Bearer", et non pas "authorization: Bearer"
                'Content-Type:application/json' // "Content-Type:application/json", et non pas "Content-Type: application/json"
            ),
        ));
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // die(var_dump($http_status));

        if ($http_status === 0) {
            $data = ['error' => true];
            return $data;
        }

        if ($response === false) {
            return (curl_error($ch));
        }
        $responseData = json_decode($response, true);
        curl_close($ch);
        return $responseData;
    }

    public function getApiData()
    {
        // API login
        $postData = array(
            'grant_type' => "password",
            'username' => $this->getUsername(),
            'password' =>  $this->getPassword()
        );

        return $postData;
    }

    /**
     * Retourne le status du serveur et son nombre de joueurs connectés
     */
    public function ServeurStatut()
    {
        $server_infos = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/info/stats');

        if (isset($server_infos['Message']) && $server_infos['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $server_infos = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/info/stats');
        }

        if (isset($server_infos['uptime'])) {
            return ['success' => true, 'online' => $server_infos['onlineCount']];
        } else {
            return  ['success' => false];
        }
    }

    /**
     * Vérifie si un mot de passe est valide
     */
    public function passwordVerify($data, $username)
    {
        $apiPasswordVerify = $this->APIcall_POST($this->getServer(), $data, $this->getToken(), '/api/v1/users/' . $username . '/password/validate');

        if (isset($apiPasswordVerify['Message']) && $apiPasswordVerify['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $apiPasswordVerify = $this->APIcall_POST($this->getServer(), $data, $this->getToken(), '/api/v1/users/' . $username . '/password/validate');
        }

        if (isset($apiPasswordVerify['Message']) && $apiPasswordVerify['Message'] == "Password Correct") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Permet d'inscrire un utilisateur
     */
    public function registerUser($data)
    {
        return $this->APIcall_POST($this->getServer(), $data, $this->getToken(), '/api/v1/users/register');
    }

    /**
     * Récupère les informations d'un compte
     */
    public function getUser($data)
    {
        $user = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/users/' . $data);

        if (isset($user['Message']) && $user['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $user = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/users/' . $data);
        }

        return $user;
    }

    public function getAllUsers($page = 0)
    {
        $user = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/users?page=' . $page . '&pageSize=30');
        if (isset($user['Message']) && $user['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $user = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/users?page=' . $page . '&pageSize=30');
        }

        return $user;
    }

    public function changeEmailAccount($data, $user_id)
    {
        $user = $this->APIcall_POST($this->getServer(), $data, $this->getToken(), '/api/v1/users/' . $user_id . '/email/change');
        if (isset($user['Message']) && $user['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $user = $this->APIcall_POST($this->getServer(), $data, $this->getToken(), '/api/v1/users/' . $user_id . '/email/change');
        }
        if (isset($user['Id'])) {
            return true;
        } else {
            return false;
        }
    }

    public function changePasswordAccount($data, $user_id)
    {
        $user = $this->APIcall_POST($this->getServer(), $data, $this->getToken(), '/api/v1/users/' . $user_id . '/manage/password/change');
        if (isset($user['Message']) && $user['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $user = $this->APIcall_POST($this->getServer(), $data, $this->getToken(), '/api/v1/users/' . $user_id . '/manage/password/change');
        }

        if ($user['Message'] == "Password Updated." || $user['Message'] == "Password Correct") {
            return true;
        } else {
            return false;
        }
    }

    public function getGameClass($data)
    {
        $classes = $this->APIcall_POST($this->getServer(), $data, $this->getToken(), '/api/v1/gameobjects/class');

        if (isset($classes['Message']) && $classes['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $classes = $this->APIcall_POST($this->getServer(), $data, $this->getToken(), '/api/v1/gameobjects/class');
        }

        return $classes;
    }

    public function getCharacter()
    {
    }

    public function getCharacters($id)
    {
        $players = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/users/' . $id . '/players');
        if (isset($players['Message']) && $players['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $players = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/users/' . $id . '/players');
        }
        return $players;
    }

    public function getAllPlayers($page)
    {

        $joueurs = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/players?page=' . $page . '&pageSize=30');
        if (isset($joueurs['Message']) && $joueurs['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $joueurs = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/players?page=' . $page . '&pageSize=30');
        }
        return $joueurs;
    }

    public function isInventoryFull($id)
    {
        $inventory = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/players/' . $id . '/items/inventory');

        if ($this->find_key_value($inventory, 'ItemId', '00000000-0000-0000-0000-000000000000')) {
            return false;
        } else {
            return true;
        }
    }

    public function onlinePlayers()
    {
        $data = [
            'page' => 0,
            'size' => 0
        ];
        $online = $this->APIcall_POST($this->getServer(), $data, $this->getToken(), '/api/v1/players/online');
        if (isset($online['Message']) && $online['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $online = $this->APIcall_POST($this->getServer(), $data, $this->getToken(), '/api/v1/players/online');
        }
        return $online['entries'];
    }

    public function getAllItems($page = 0)
    {
        $data = [
            'page' => $page,
            'count' => 20
        ];

        $items = $this->APIcall_POST($this->getServer(), $data,  $this->getToken(), '/api/v1/gameobjects/item/');
        if (isset($items['Message']) && $items['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $items = $this->APIcall_POST($this->getServer(), $data,  $this->getToken(), '/api/v1/gameobjects/item/');
        }
        return $items;
    }

    public function getObjectDetail($id)
    {
        $itemData = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/gameobjects/item/' . $id);
        $itemData = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/gameobjects/item/' . $id);
        if (isset($itemData['Message']) && $itemData['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $itemData = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/gameobjects/item/' . $id);
        }
        return $itemData;
    }

    /**
     * Récupère le classement général de l'API
     */

    public function getRank()
    {
        $joueurs = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/players/rank?page=0&pageSize=25&sortDirection=Descending');
        if (isset($joueurs['Message']) && $joueurs['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $joueurs = $this->APIcall_GET($this->getServer(), $this->getToken(), '/api/v1/players/rank?page=0&pageSize=25&sortDirection=Descending');
        }
        return $joueurs['Values'];
    }


    public function giveItem($data, $character)
    {
        $item = $this->APIcall_POST($this->getServer(), $data,  $this->getToken(), '/api/v1/players/' . $character . '/items/give');
        if (isset($item['Message']) && $item['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $item = $this->APIcall_POST($this->getServer(), $data,  $this->getToken(), '/api/v1/players/' . $character . '/items/give');
        }

        if (isset($item['id'])) {
            return true;
        } else {
            return false;
        }
    }


    // Admin Action

    public function banAccount($user_id, $username, $duration = 5, $moderator)
    {
        $data = [
            'duration' => $duration,
            'reason' => "Ban from web interface",
            'moderator' => $moderator
        ];
        $ban = $this->APIcall_POST($this->getServer(), $data,  $this->getToken(), '/api/v1/users/' . $user_id . '/admin/ban');
        if (isset($ban['Message']) && $ban['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $ban = $this->APIcall_POST($this->getServer(), $data,  $this->getToken(), '/api/v1/users/' . $user_id . '/admin/ban');
        }

        if (isset($ban['Message']) && $ban['Message'] == $username . ' has been banned!') {
            return true;
        } else {
            return false;
        }
    }

    public function unBanAccount($user_id, $username)
    {
        $data = [
            'username' => $username
        ];

        $ban = $this->APIcall_POST($this->getServer(), $data,  $this->getToken(), '/api/v1/users/' . $user_id . '/admin/unban');
        if (isset($ban['Message']) && $ban['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $ban = $this->APIcall_POST($this->getServer(), $data,  $this->getToken(), '/api/v1/users/' . $user_id . '/admin/unban');
        }

        if (isset($ban['Message']) && $ban['Message'] == 'Account ' . $username . ' has been unbanned!') {
            return true;
        } else {
            return false;
        }
    }


    public function MuteAccount($user_id, $username, $duration = 5, $moderator)
    {
        $data = [
            'duration' => $duration,
            'reason' => "Mute from web interface",
            'moderator' => $moderator
        ];
        $ban = $this->APIcall_POST($this->getServer(), $data,  $this->getToken(), '/api/v1/users/' . $user_id . '/admin/mute');
        if (isset($ban['Message']) && $ban['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $ban = $this->APIcall_POST($this->getServer(), $data,  $this->getToken(), '/api/v1/users/' . $user_id . '/admin/mute');
        }

        if (isset($ban['Message']) && $ban['Message'] == $username . ' has been muted!') {
            return true;
        } else {
            return false;
        }
    }


    public function unMuteAccount($user_id, $username)
    {
        $data = [
            'username' => $username
        ];

        $ban = $this->APIcall_POST($this->getServer(), $data,  $this->getToken(), '/api/v1/users/' . $user_id . '/admin/unmute');
        if (isset($ban['Message']) && $ban['Message'] == "Authorization has been denied for this request.") {
            $this->setToken();
            $ban = $this->APIcall_POST($this->getServer(), $data,  $this->getToken(), '/api/v1/users/' . $user_id . '/admin/unmute');
        }

        if (isset($ban['Message']) && $ban['Message'] == $username . ' has been unmuted!') {
            return true;
        } else {
            return false;
        }
    }


    // Configuration Serveur

    public function getServerConfig()
    {
            $server = $this->APIcall_GET($this->getServer(),  $this->getToken(), '/api/v1/info/config');
            if (isset($server['Message']) && $server['Message'] == "Authorization has been denied for this request.") {
                $this->setToken();
                $server = $this->APIcall_GET($this->getServer(),  $this->getToken(), '/api/v1/info/config');
            }
            
            return $server;
        
    }


    /**
     * Get the value of server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Get the value of password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Get the value of username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get the value of token
     */
    public function getToken()
    {
        return $this->token;
    }

    public function getDedipassPublic()
    {
        return $this->dedipass_public;
    }

    public function getDedipassPrivate()
    {
        return $this->dedipass_private;
    }

    /**
     * Set the value of token
     *
     * @return  self
     */
    public function setToken()
    {
        $loginAPI = $this->APIcall_POST($this->getServer(), $this->getApiData(), "", '/api/oauth/token');
        $newToken = $this->em->getRepository(CmsSettings::class)->findOneBy(['setting' => 'api_token']);
        $newToken->setDefaultValue($loginAPI['access_token']);
        $this->em->persist($newToken);
        $this->em->flush();


        $this->token = $newToken->getDefaultValue();

        return $this;
    }


    function find_key_value($array, $key, $val)
    {
        foreach ($array as $item) {
            if (is_array($item) && $this->find_key_value($item, $key, $val)) return true;

            if (isset($item[$key]) && $item[$key] == $val) return true;
        }

        return false;
    }
}
