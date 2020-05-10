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
    protected $db;
    protected $clientId;
    protected $artistName;
    
    public function __construct(object $db, int $clientId, string $artistName) 
    {
        $this->db = $db;
        $this->clientId = (int)$clientId;
        $this->artistName = $artistName;
        parent::__construct();
    }
    
    public function list()
    {
        //Get Artist by name
        $artistByNameInstance = new GetArtistByName($this->db,$this->clientId,$this->artistName);
        $artist = $artistByNameInstance->get();
        if(!empty($artist[0]['id'])){
            $this->artistId = $artist[0]['id'];
            $albums = $this->get_artist_albums();
            if(!empty($albums))
            {
                return $this->parseResponse($albums);
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
