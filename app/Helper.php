<?php

namespace App;

use function _\flatten;
use function _\map;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class Helper {

  /**
   * @param $url
   *
   * @return bool|string
   */
  public static function requestData( $url ) {
    return file_get_contents( $url );
  }

  /**
   * @param            $data
   * @param            $modelClass
   * @param bool       $isAddHomeworldSpecies
   *
   * @return array
   */
  public static function hydrateData( $data, $modelClass, bool $isAddHomeworldSpecies = false ): array {
    $obj = [];
    $serializer = new Serializer( [ new ObjectNormalizer() ], [ new JsonEncoder() ] );

    try {
      $dataArr = json_decode( $data )->results;

      foreach ( $dataArr as $key => $value ) {
        $newObj = self::deserializeObj( $serializer, $value, $modelClass );

        if ( $isAddHomeworldSpecies ) {
          $newObj->homeworld = json_decode( self::requestData( $newObj->homeworld ) )->name;
            $newObj->species = \count( $newObj->species ) > 0
              ? json_decode( self::requestData( $newObj->species[ 0 ] ) )->name
              : '';
        }

        $obj[] = $newObj;
      }
    } catch ( \Exception $ex ) {
      return [];
    }

    return $obj;
  }

  /**
   * @param Serializer $serializer
   * @param            $data
   * @param            $modelClass
   *
   * @return object
   */
  public static function deserializeObj( Serializer $serializer, $data, $modelClass ) {
    return $serializer->deserialize( json_encode( $data, JSON_UNESCAPED_SLASHES ), $modelClass, 'json' );
  }

  /**
   * @param array $urls
   * @param       $modelClass
   * @param bool  $isSingleResult
   *
   * @return mixed
   */
  public static function hydrateModel( array $urls, $modelClass, bool $isSingleResult = false ) {
    $results = [];
    $serializer = new Serializer( [ new ObjectNormalizer() ], [ new JsonEncoder() ] );

    foreach ( $urls as $url ) {
      $data = self::requestData( $url );
      $arr = json_decode( $data, true );
      $results[] = self::deserializeObj( $serializer, $arr, $modelClass );
    }

    if ( $isSingleResult && \count( $results ) > 0 ) {
      return $results[ 0 ];
    }

    return $results;
  }

  /**
   * @param bool $isSortNameAsc
   *
   * @return array
   */
  public static function getCharacterNames( bool $isSortNameAsc = true ): array {
    $characterNames = [];

    try {
      $nextUrl = URL_PEOPLE;

      do {
        $data = json_decode( self::requestData( $nextUrl ) );
        $nextUrl = $data->next ?? '';
        $results = $data->results;
        $characterNames[] = map( $results, function ( $result ) {
          return $result->name;
        } );
      } while ( $nextUrl !== null && $nextUrl !== '' );

      $characterNames = flatten( $characterNames );
    } catch ( \Exception $ex ) {
      dd( $ex ); // NOTE: Debugging only -> Not for prod
    }

    if ( $isSortNameAsc ) {
      sort( $characterNames );
    }

    return $characterNames;
  }

}

//#region  CONSTANTS

//\define( 'BASE_ASSETS_HOST', '/firespring-proj/public/' ); // TODO: Use with XAMPP or if path differs from '/'
\define( 'BASE_ASSETS_HOST', '/' ); // TODO: Default base path (used with `php artisan serve`)
\define( 'BASE_URL', env( 'SWAPI_BASE_URL', 'https://swapi.co/api/' ) );
\define( 'URL_PEOPLE', BASE_URL . 'people' );
\define( 'URL_PLANET', BASE_URL . 'planets' );
\define( 'URL_FILM', BASE_URL . 'films' );
\define( 'URL_SPECIES', BASE_URL . 'species' );
\define( 'URL_STARSHIP', BASE_URL . 'starships' );
\define( 'URL_VEHICLES', BASE_URL . 'vehicles' );

//#endregion  CONSTANTS
