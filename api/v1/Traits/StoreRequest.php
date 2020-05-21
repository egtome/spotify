<?php
/**
 * Trait StoreRequest to use in any API endpoint 
 *
 * @author Gino Tome
 */
namespace Api\v1\Traits;

use Api\v1\Classes\Request;
trait StoreRequest {

    public function storeRequest(string $responseJson, int $timeElapsed) :? bool
    {
        $ip = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        
        $req = new Request($this->db);
        $req->setClientId($this->clientId);
        $req->setIpAddress($ip);
        $req->setSearchTerm($this->searchTerm);
        $req->setResponse($responseJson);
        $req->setResponseTime($timeElapsed);
        
        $return = $req->storeByClientId();
        unset($req);
        return $return;
    }
}
