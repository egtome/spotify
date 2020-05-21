<?php
namespace Api\v1\Classes;

/**
 * Manage clients table
 *
 * @author gino
 */
class Request {
    const TABLE = 'requests';    
    protected $db;
    
    public $id;
    public $clientId;
    public $ipAddress;
    public $searchTerm;
    public $response;
    public $responseTime;
    public $dateRequested;

    public function __construct(object $db) 
    {
        $this->db = $db;
    }     
    
    public function storeByClientId() :bool
    {
        $sql = 'INSERT INTO  ' . $this::TABLE
             . ' (client_id, ip_address, search_term, response, response_time) '
             . ' VALUES '
             . "(:client_id, :ip_address, :search_term, :response, :response_time);";
        $stmt = $this->db->prepare($sql);
        return (bool) $stmt->execute([':client_id' => $this->clientId,
                                      ':ip_address' => $this->ipAddress,
                                      ':search_term' => $this->searchTerm,
                                      ':response' => $this->response,
                                      ':response_time' => $this->responseTime
                                    ]);
    }    
    
    function getId() {
        return $this->id;
    }

    function getCientId() {
        return $this->clientId;
    }

    function getIpAddress() {
        return $this->ipAddress;
    }

    function getSearchTerm() {
        return $this->searchTerm;
    }

    function getResponse() {
        return $this->response;
    }

    function getResponseTime() {
        return $this->responseTime;
    }

    function getDateRequested() {
        return $this->dateRequested;
    }

    function setId($id): void {
        $this->id = $id;
    }

    function setClientId(int $clientId): void {
        $this->clientId = $clientId;
    }

    function setIpAddress(string $ipAddress): void {
        $this->ipAddress = $ipAddress;
    }

    function setSearchTerm(string $searchTerm): void {
        $this->searchTerm = $searchTerm;
    }

    function setResponse(string $response): void {
        $this->response = $response;
    }

    function setResponseTime(int $responseTime): void {
        $this->responseTime = $responseTime;
    }

    function setDateRequested(string $dateRequested): void {
        $this->dateRequested = $dateRequested;
    }

}
