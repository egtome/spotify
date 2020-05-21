<?php
namespace Api\v1\Artist\Album;
use Psr\Container\ContainerInterface;
use Api\v1\Artist\GetArtistByName;
use Api\v1\Classes\Core;
/**
 * List albums by artist name
 *
 * @author gino
 */
class ListByArtistName Extends Core{
    use \Api\v1\Traits\StoreRequest;
    
    protected $db;
    protected $clientId;
    protected $searchTerm;
    
    public function __construct(object $db, int $clientId, string $artistName) 
    {
        $this->db = $db;
        $this->clientId = (int)$clientId;
        $this->searchTerm = $artistName;
        parent::__construct();
    }
    
    public function list()
    {
        
        $start = time();
        //Get Artist by name
        $artistByNameInstance = new GetArtistByName($this->db,$this->clientId,$this->searchTerm);
        $artist = $artistByNameInstance->get();
        if(!empty($artist[0]['id'])){
            $this->artistId = $artist[0]['id'];
            $albums = $this->get_artist_albums();
            if(!empty($albums))
            {
                $timeElapsed = (time() - $start);
                $responseJson = $this->parseResponse($albums);
                //Store request. This should be as an event with a listener taking care of it
                $this->storeRequest($responseJson,$timeElapsed);
                return $responseJson;
            }else
            {
                $this->throw_exception('No albums found', 404);
            }
        } 
        else 
        {
            $this->throw_exception('No artist found', 404);
        }
    }
    
    protected function parseResponse(array $data)
    {
        $return = [];
        if(!empty($data))
        {
            $i = 0;
            foreach($data as $item)
            {
                $return[$i]['name'] = $item['name'];
                $return[$i]['released'] = date('d-m-Y', strtotime($item['release_date']));
                $return[$i]['tracks'] = $item['total_tracks'];
                //Cover image
                $return[$i]['cover']['height'] = (!empty($item['images'][0]['height'])) ? $item['images'][0]['height'] : null;
                $return[$i]['cover']['width'] = (!empty($item['images'][0]['width'])) ? $item['images'][0]['width'] : null;
                $return[$i]['cover']['url'] = (!empty($item['images'][0]['url'])) ? $item['images'][0]['url'] : null;                

                $i++;
            }
        }
        return json_encode($return);
        //return $return;
    }

}
