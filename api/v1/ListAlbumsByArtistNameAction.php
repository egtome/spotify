<?php
namespace Api\v1;
use Psr\Container\ContainerInterface;
use Api\v1\Artist\Album\ListByArtistName;
/**
 * List all albums by artist name
 *
 * @author Gino Tome
 */
class ListAlbumsByArtistNameAction {
   protected $container;

   public function __construct(ContainerInterface $container) {
       $this->container = $container;
   }

   public function __invoke($request, $response, $args) 
   {
        $artistName = $request->getQueryParam('q', $default = null);
        //For testing purposes, client ID is always 1 but this should come from Request and validated in middleware
        $listArtistAlbums = New ListByArtistName($this->container['db'],1,$artistName);
        $albums = $listArtistAlbums->list();
        return $albums;
   }
}
