<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Api\Classes;
use Api\Classes\Client;
use Api\Classes\ClientCredentials;

/**
 * Spotify API core
 *
 * @author gino
 */
class Core {
    
    const GENERATE_TOKEN_URL = 'https://accounts.spotify.com/api/token';
    const GENERIC_SEARCH_URL = 'https://api.spotify.com/v1/search';
    const ARTIST_ALBUMS_SEARCH_URL = 'https://api.spotify.com/v1/artists/[artist_id]/albums';
    const SEARCH_TYPE_ARTIST = 'artist';
    
    private $accessToken;
    
    public function __construct() 
    {
        $this->get_token();
    }    
    
    protected function search_artist_by_name()
    {
        return $this->generic_search($this->artistName, self::SEARCH_TYPE_ARTIST);
    }
    
    private function get_key_by_type(string $type)
    {
        $types = [
            'artist' => 'artists',
            'album' => 'albums',
        ];
        if(isset($types[$type])){
            return $types[$type];
        }else{
            return null;
        }
    }
    
    protected function get_artist_albums(?array $params = [])
    {
        $url = str_replace('[artist_id]', $this->artistId, $this::ARTIST_ALBUMS_SEARCH_URL);
        
        $authHeader = $this->generate_auth_header_token();
        $params = [
            'query' => [
                'limit' => 50
            ],
            'headers' => [
                'Authorization' => $authHeader
            ]
        ];

        $result = $this->get_request($url, $params);

        $pagination = $this->no_key_pagination($result);
        
        if(!empty($pagination))
        {
            unset($result);
            return $pagination;
        }elseif(!empty($result['items'])){
            return $result['items'];
        }else{
            return [];
        }        
        echo '<pre>';var_dump($url,$result);die();
    }   
    
    private function generic_search(string $query, string $type)
    {     
        $key = $this->get_key_by_type($type);
        if($key === null)
        {
            $this->throw_exception('Resource key not found',500);
        }
        
        $authHeader = $this->generate_auth_header_token();
        $params = [
            'query' => [
                'q' => $query,
                'type' => $type,
                'limit' => 50
            ],
            'headers' => [
                'Authorization' => $authHeader
            ]
        ];

        $result = $this->get_request($this::GENERIC_SEARCH_URL, $params);
        $pagination = $this->pagination($result,$key);
        
        if(!empty($pagination))
        {
            unset($result);
            return $pagination;
        }elseif(!empty($result[$key]['items'])){
            return $result[$key]['items'];
        }else{
            return [];
        }
    }   
    
    private function pagination(array $data, string $key)
    {
        $return = [];
        $authHeader = $this->generate_auth_header_token();
        $params = [
            'headers' => [
                'Authorization' => $authHeader
            ]
        ];          
        $next = !empty($data[$key]['next']) ? $data[$key]['next'] : null; 
        while($next !== null){
            $result = $this->get_request($next, $params);         
            if(!empty($result[$key]['items'])){
                $return += $result[$key]['items'];
            }
            $next = !empty($result[$key]['next']) ? $result[$key]['next'] : null; 
        }
        if(!empty($return)){
            return array_merge($data[$key]['items'],$return);
        }
        return $return;
    }
    
    private function no_key_pagination(array $data)
    {
        $return = [];
        $authHeader = $this->generate_auth_header_token();
        $params = [
            'headers' => [
                'Authorization' => $authHeader
            ]
        ];          
        $next = !empty($data['next']) ? $data['next'] : null; 
        while($next !== null){
            $result = $this->get_request($next, $params);         
            if(!empty($result['items'])){
                $return += $result['items'];
            }
            $next = !empty($result['next']) ? $result['next'] : null; 
        }
        if(!empty($return)){
            return array_merge($data['items'],$return);
        }
        return $return;
    }
    
    /**
     * Get client token
     * Validates client
     * Check if a token exists for specified client (use ID 1 for testing)
     * If token does not exist or is expired, generates a new one
     * If user does not exist return error
     * If token generation fails return error
     * @param integer $client_id client ID to request token from
     * 
     * @throws Exception on error
     * @author Gino Tome <ginotome@gmail.com>
     * @return string 
     */     
    protected function get_token()
    {
        $token = $this->get_valid_client_token();
        if($token === null){
            //Get client data
            $client = $this->get_client_by_id();
            if($client === null || empty($client['client_auth_id']) || empty($client['client_auth_secret']))
            {
                $this->throw_exception('Invalid client provided',400);
            }else{
                //Generate new token for client
                $token = $this->generate_token($client);
            }
        }
        $this->accessToken = $token;
    }
    
    private function get_valid_client_token() :?string
    {
        $clientCredentials = new ClientCredentials($this->db);
        $clientCredentials->setClientId($this->clientId);
        return $clientCredentials->getValidTokenByClientId();
    }
    
    private function get_client_by_id() :?array
    {
        $client = new Client($this->db);
        $client->setId($this->clientId);
        return $client->getClientById();
    }

    /**
     * Return generic exception with error message
     * @param integer $client_id client ID to request token from
     * 
     * @throws Exception on error
     * @author Gino Tome <ginotome@gmail.com>
     * @return string 
     */    
    protected function throw_exception(string $message, int $code) :object
    {
        throw new \Exception($message,$code);
    }
    
    private function generate_token(array $client)
    {
        $authHeader = $this->generate_auth_header($client);
        $params = [
            'form_params' => [
                'grant_type' => 'client_credentials'
            ],
            'headers' => [
                'Authorization' => $authHeader
            ]
        ];
        $response = $this->post_request($this::GENERATE_TOKEN_URL, $params);
        if(empty($response['access_token']))
        {
            $this->throw_exception('Unable to generate token',500);
        }
        //Store token in DB
        $clientCredentials = new ClientCredentials($this->db);
        $clientCredentials->setClientId($this->clientId);
        $clientCredentials->setAccessToken($response['access_token']);
        $clientCredentials->setExpiresIn(($response['expires_in'] - 120));
        
        $save = $clientCredentials->storeTokenByClientId(); 
        if(!$save){
            $this->throw_exception('Unable to store token',500);
        }
        return $response['access_token'];
    } 
    
    private function generate_auth_header(array $client) :string
    {
        $client_id = $client['client_auth_id'];
        $client_secret = $client['client_auth_secret'];
        $header = 'Basic ' . base64_encode("$client_id:$client_secret");
        
        return $header;
    }
    
    private function generate_auth_header_token()
    {
        return 'Bearer ' . $this->accessToken;
    }
    
    private function post_request(string $url, array $params) :?array
    {
        $response = (new \GuzzleHttp\Client)->post($url, $params);
        
        $responseJson = (string) $response->getBody();
        $responseArray = json_decode($responseJson,true);
        
        return $responseArray;
    }
    
    private function get_request(string $url, array $params) :?array
    {
        $response = (new \GuzzleHttp\Client)->get($url, $params);
        
        $responseJson = (string) $response->getBody();
        $responseArray = json_decode($responseJson,true);
        
        return $responseArray;
    }
}
