<?php

namespace Api\v1\Classes;
use Api\v1\Classes\Client;
use Api\v1\Classes\ClientCredentials;

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
    
    /**
     * Search by artist name
     * @author Gino Tome <ginotome@gmail.com>
     * @return array 
     */    
    protected function search_artist_by_name()
    {
        return $this->generic_search($this->artistName, self::SEARCH_TYPE_ARTIST);
    }
    
    /**
     * Get key by type of request, the key containing the data in API response
     * @param string $type
     * @author Gino Tome <ginotome@gmail.com>
     * @return string 
     */        
    private function get_key_by_type(string $type) :? string 
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
    
    /**
     * Get all albums from specific artist
     * @param array $params
     * @author Gino Tome <ginotome@gmail.com>
     * @return array 
     */            
    protected function get_artist_albums(?array $params = []) :? array
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
    }   
    
    
    /**
     * Generic search - search any item in API
     * @param string $query string $type
     * @author Gino Tome <ginotome@gmail.com>
     * @return array 
     */        
    private function generic_search(string $query, string $type) :? array
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
    
    /**
     * Paginates API response (if needed)
     * @param array $data string $key
     * @author Gino Tome <ginotome@gmail.com>
     * @return array 
     */      
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
    
    /**
     * Paginates API response with no key data (if needed) 
     * @param array $data string $key
     * @author Gino Tome <ginotome@gmail.com>
     * @return array 
     */          
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
     * If user does not exist return error exception
     * If token generation fails return error exception
     * @param integer $client_id client ID to request token from
     * 
     * @throws Exception on error
     * @author Gino Tome <ginotome@gmail.com>
     * @return string 
     */     
    private function get_token()
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
    
    /**
     * Generate access token based on client credentials
     * If user does not exist return error exception
     * If token generation fails return error exception
     * @param array $client 
     * 
     * @throws Exception on error
     * @author Gino Tome <ginotome@gmail.com>
     * @return string 
     */      
    private function generate_token(array $client) : string 
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
        
        //Store token in database
        $this->store_token($response);

        return $response['access_token'];
    }    
    
    /**
     * Store generated token in database
     * if DB operaton fails return error exception
     * @param array $data 
     * 
     * @throws Exception on error
     * @author Gino Tome <ginotome@gmail.com>
     * @return boolean 
     */     
    private function store_token(array $data) : bool
    {
        //Store token in DB
        $clientCredentials = new ClientCredentials($this->db);
        $clientCredentials->setClientId($this->clientId);
        $clientCredentials->setAccessToken($data['access_token']);
        $clientCredentials->setExpiresIn(($data['expires_in'] - 120));
        
        $save = (bool)$clientCredentials->storeTokenByClientId();  
        if(!$save){
            $this->throw_exception('Unable to store token',500);
        }
        return $save;
    }
    
    /**
     * Get a valid token from database (valid = non expired and existing token)

     * @author Gino Tome <ginotome@gmail.com>
     * @return string 
     */        
    private function get_valid_client_token() :?string
    {
        $clientCredentials = new ClientCredentials($this->db);
        $clientCredentials->setClientId($this->clientId);
        return $clientCredentials->getValidTokenByClientId();
    }
    
    /**
     * Get client by ID

     * @author Gino Tome <ginotome@gmail.com>
     * @return array 
     */       
    private function get_client_by_id() :?array
    {
        $client = new Client($this->db);
        $client->setId($this->clientId);
        return $client->getClientById();
    }

    /**
     * Return generic exception with error message
     * @param string $message int $code
     * 
     * @throws Exception on error
     * @author Gino Tome <ginotome@gmail.com>
     * @return object 
     */    
    protected function throw_exception(string $message, int $code) :object
    {
        throw new \Exception($message,$code);
    } 
    
    /**
     * Generate authorization header in format to request access token
     * @param array $client
     * @author Gino Tome <ginotome@gmail.com>
     * @return string 
     */           
    private function generate_auth_header(array $client) :string
    {
        $client_id = $client['client_auth_id'];
        $client_secret = $client['client_auth_secret'];
        $header = 'Basic ' . base64_encode("$client_id:$client_secret");
        
        return $header;
    }
    
    /**
     * Generate authorization header to auth with API
     * @author Gino Tome <ginotome@gmail.com>
     * @return string 
     */                 
    private function generate_auth_header_token() : string
    {
        return 'Bearer ' . $this->accessToken;
    }
    
    /**
     * send POST request to API
     * on error throw exception
     * @author Gino Tome <ginotome@gmail.com>
     * @return array 
     */      
    private function post_request(string $url, array $params) :?array
    {
        $responseArray = null;
        try{
            $response = (new \GuzzleHttp\Client)->post($url, $params);

            $responseJson = (string) $response->getBody();
            $responseArray = json_decode($responseJson,true);            
        } catch (\Exception $ex) {
            $this->throw_exception($ex->getMessage(), $ex->getCode());
        }
        
        return $responseArray;
    }
    
    /**
     * send GET request to API
     * on error throw exception
     * @author Gino Tome <ginotome@gmail.com>
     * @return array 
     */    
    private function get_request(string $url, array $params) :?array
    {
        $responseArray = null;
        try{
            $response = (new \GuzzleHttp\Client)->get($url, $params);

            $responseJson = (string) $response->getBody();
            $responseArray = json_decode($responseJson,true);        
        } catch (\Exception $ex) {
            $this->throw_exception($ex->getMessage(), $ex->getCode());
        }
        
        return $responseArray;        
    }
}
