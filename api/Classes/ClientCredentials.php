<?php
namespace Api\Classes;

/**
 * Manage clients table
 *
 * @author gino
 */
class ClientCredentials {
    const TABLE = 'client_credentials';

    protected $db;
    
    public $clientId;
    public $accessToken;
    public $expiresIn;
    
    public function __construct(object $db) 
    {
        $this->db = $db;
    }    
    
    public function getValidTokenByClientId() :?string
    {
        $stmt = $this->db->prepare('SELECT * FROM ' . $this::TABLE . ' WHERE client_id = :client_id AND expires_at > NOW()');
        $stmt->execute([':client_id' => $this->clientId]); 
        $client_credentials = $stmt->fetchAll();     
        if(empty($client_credentials[0]['access_token'])){
            return null;
        }else{
            return $client_credentials[0]['access_token'];
        }        
    }
    
    public function storeTokenByClientId() :bool
    {
        $sql = 'INSERT INTO  ' . $this::TABLE
             . ' (client_id, access_token, created_at, expires_at) '
             . ' VALUES '
             . "(:client_id, :access_token, NOW(), DATE_ADD(NOW(), INTERVAL $this->expiresIn SECOND));";
        $stmt = $this->db->prepare($sql);
        return (bool) $stmt->execute([':client_id' => $this->clientId,':access_token' => $this->accessToken]);
    }    
    
    
    function getClientId() {
        return $this->clientId;
    }

    function getAccessToken() {
        return $this->accessToken;
    }

    function setClientId($clientId): void {
        $this->clientId = $clientId;
    }

    function setAccessToken($accessToken): void {
        $this->accessToken = $accessToken;
    }

    function getExpiresIn() {
        return $this->expiresIn;
    }

    function setExpiresIn($expiresIn): void {
        $this->expiresIn = $expiresIn;
    }


}
