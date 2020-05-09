<?php
namespace Api\Artist;
use Psr\Container\ContainerInterface;
use Api\Classes\Core;
/**
 * List albums by artist name
 *
 * @author gino
 */
class GetArtistByName Extends Core{
    protected $db;
    protected $clientId;
    protected $artistName;
    protected $artistId;
    
    public function __construct(object $db, int $clientId, string $artistName) 
    {
        $this->db = $db;
        $this->clientId = $clientId;
        $this->artistName = $artistName;
        parent::__construct();
    }
    
    public function get()
    {
        //Get Artist by name
        return $this->search_artist_by_name();
    }
}
