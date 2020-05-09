<?php
namespace Api\Classes;

/**
 * Manage clients table
 *
 * @author gino
 */
class Client {
    const TABLE = 'clients';    
    protected $db;
    
    public $id;
    public $name;
    public $clientAuthId;
    public $clientAuthSecret;

    public function __construct(object $db) 
    {
        $this->db = $db;
    }     
    
    public function getClientById() :?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ' . $this::TABLE . ' WHERE id = :client_id');
        $stmt->execute([':client_id' => $this->id]); 
        $clientData = $stmt->fetchAll(); 
        if(empty($clientData[0]['client_auth_id']) || empty($clientData[0]['client_auth_secret'])){
            return null;
        }else{
            return $clientData[0];
        }        
    }    
    
    function getId() {
        return $this->id;
    }

    function setId($id): void {
        $this->id = $id;
    }
    
    
    function getName() {
        return $this->name;
    }

    function getClientAuthId() {
        return $this->clientAuthId;
    }

    function getClientAuthSecret() {
        return $this->clientAuthSecret;
    }

    function setName($name): void {
        $this->name = $name;
    }

    function setClientAuthId($clientAuthId): void {
        $this->clientAuthId = $clientAuthId;
    }

    function setClientAuthSecret($clientAuthSecret): void {
        $this->clientAuthSecret = $clientAuthSecret;
    }
}
