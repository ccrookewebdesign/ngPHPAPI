<?php

/**
 * JSON Web Token implementation, based on this spec:
 * https://tools.ietf.org/html/rfc7519
 *
 * PHP version 5
 *
 * @category Authentication
 * @package  Authentication_JWT
 * @author   Neuman Vong <neuman@twilio.com>
 * @author   Anant Narayanan <anant@php.net>
 * @license  http://opensource.org/licenses/BSD-3-Clause 3-clause BSD
 * @link     https://github.com/firebase/php-jwt
 * 
 * Chris Crooke edited response handling in decode function
 */

class JWT {

  public static $leeway = 0;

  public static $timestamp = null;

  public static $supported_algs = array(
    'HS256' => array('hash_hmac', 'SHA256'),
    'HS512' => array('hash_hmac', 'SHA512'),
    'HS384' => array('hash_hmac', 'SHA384'),
    'RS256' => array('openssl', 'SHA256'),
    'RS384' => array('openssl', 'SHA384'),
    'RS512' => array('openssl', 'SHA512'),
  );

  /**
  * Decodes a JWT string into a PHP object.
  *
  * @param string        $jwt            The JWT
  *  @param string|array  $key            The key, or map of keys.
  *                                      If the algorithm used is asymmetric, this is the public key
  * @param array         $allowed_algs   List of supported verification algorithms
  *                                      Supported algorithms are 'HS256', 'HS384', 'HS512' and 'RS256'
  *
  * @return object The JWT's payload as a PHP object
  *
  * @throws UnexpectedValueException     Provided JWT was invalid
  * @throws SignatureInvalidException    Provided JWT was invalid because the signature verification failed
  * @throws BeforeValidException         Provided JWT is trying to be used before it's eligible as defined by 'nbf'
  * @throws BeforeValidException         Provided JWT is trying to be used before it's been created as defined by 'iat'
  * @throws ExpiredException             Provided JWT has since expired, as defined by the 'exp' claim
  *
  * @uses jsonDecode
  * @uses urlsafeB64Decode
  **/
    
  public static function decode(
    $jwt, $key, array $allowed_algs = array()
  ) {
        
    $response = array();

    $timestamp = is_null(static::$timestamp) ? time() : static::$timestamp;

    if (empty($key)) {
  
      //throw new InvalidArgumentException('Key may not be empty');
      $response['success'] = false;
      $response['message'] = 'Key may not be empty.';
      $response['errcode'] = 'jwt-invalidargument';

      return $response;
  
    }
  
    $tks = explode('.', $jwt);
    
    if (count($tks) != 3) {
    
      //throw new UnexpectedValueException('Wrong number of segments');
      $response['success'] = false;
      $response['message'] = 'Wrong number of segments.';
      $response['errcode'] = 'jwt-unexpectedvalue';

      return $response;
    
    }
    
    list($headb64, $bodyb64, $cryptob64) = $tks;
    
    if (null === ($header = static::jsonDecode(static::urlsafeB64Decode($headb64)))) {
    
      //throw new UnexpectedValueException('Invalid header encoding');
      $response['success'] = false;
      $response['message'] = 'Invalid header encoding.';
      $response['errcode'] = 'jwt-unexpectedvalue';

      return $response;

    }

    if (null === $payload = static::jsonDecode(static::urlsafeB64Decode($bodyb64))) {

      //throw new UnexpectedValueException('Invalid claims encoding');
      $response['success'] = false;
      $response['message'] = 'Invalid claims encoding.';
      $response['errcode'] = 'jwt-unexpectedvalue';


      return $response;
      
    }
    
    if (false === ($sig = static::urlsafeB64Decode($cryptob64))) {
    
      //throw new UnexpectedValueException('Invalid signature encoding');
      $response['success'] = false;
      $response['message'] = 'Invalid header encoding.';
      $response['errcode'] = 'jwt-unexpectedvalue';

      return $response;
    
    }
    
    if (empty($header->alg)) {
    
      //throw new UnexpectedValueException('Empty algorithm');
      $response['success'] = false;
      $response['message'] = 'Empty algorithm.';
      $response['errcode'] = 'jwt-unexpectedvalue';

      return $response;

    }

    if (empty(static::$supported_algs[$header->alg])) {

      //throw new UnexpectedValueException('Algorithm not supported');
      $response['success'] = false;
      $response['message'] = 'Algorithm not supported.';
      $response['errcode'] = 'jwt-unexpectedvalue';

      return $response;

    }

    if (!in_array($header->alg, $allowed_algs)) {

      //throw new UnexpectedValueException('Algorithm not allowed');
      $response['success'] = false;
      $response['message'] = 'Algorithm not allowed.';
      $response['errcode'] = 'jwt-unexpectedvalue';

      return $response;

    }

    if (is_array($key) || $key instanceof \ArrayAccess) {

      if (isset($header->kid)) {

        if (!isset($key[$header->kid])) {

          //throw new UnexpectedValueException('"kid" invalid, unable to lookup correct key');
          $response['success'] = false;
          $response['message'] = '"kid" invalid, unable to lookup correct key.';
          $response['errcode'] = 'jwt-unexpectedvalue';

          return $response;

        }

        $key = $key[$header->kid];

      } else {

        //throw new UnexpectedValueException('"kid" empty, unable to lookup correct key');
        $response['success'] = false;
        $response['message'] = '"kid" empty, unable to lookup correct key.';
        $response['errcode'] = 'jwt-unexpectedvalue';

        return $response;

      }

    }

    if (!static::verify("$headb64.$bodyb64", $sig, $key, $header->alg)) {

      //throw new SignatureInvalidException('Signature verification failed');
      $response['success'] = false;
      $response['message'] = 'Signature verification failed.';
      $response['errcode'] = 'jwt-signatureinvalid';

      return $response;

    }

    if (isset($payload->nbf) && $payload->nbf > ($timestamp + static::$leeway)) {
      /* throw new BeforeValidException(
        'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->nbf)
      ); */

      $response['success'] = false;
      $response['message'] = 'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->nbf) . '.';
      $response['errcode'] = 'jwt-beforevalid';

      return $response;

    }

    if (isset($payload->iat) && $payload->iat > ($timestamp + static::$leeway)) {

      /* throw new BeforeValidException(
        'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->iat)
      ); */
  
      $response['success'] = false;
      $response['message'] = 'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->iat) . '.';
      $response['errcode'] = 'jwt-beforevalid';

      return $response;
  
    }

    if (isset($payload->exp) && ($timestamp - static::$leeway) >= $payload->exp) {
    //if(1 == 1) {
      
      //throw new ExpiredException('Expired token');
      $response['success'] = false;
      $response['message'] = 'Expired token.';
      $response['errcode'] = 'jwt-expired';

      return $response;
  
    }

    $response['success'] = true;
    $response['message'] = 'Token successfully decoded.';
    $response['data'] = $payload;
    
    return $response;
  
  }

  /**
  * Converts and signs a PHP object or array into a JWT string.
  *
  * @param object|array  $payload    PHP object or array
  * @param string        $key        The secret key.
  *                                  If the algorithm used is asymmetric, this is the private key
  * @param string        $alg        The signing algorithm.
  *                                  Supported algorithms are 'HS256', 'HS384', 'HS512' and 'RS256'
  * @param mixed         $keyId
  * @param array         $head       An array with header elements to attach
  *
  * @return string A signed JWT
  *
  * @uses jsonEncode
  * @uses urlsafeB64Encode
  **/
  
  public static function encode(
    $payload, $key, $alg = 'HS256', $keyId = null, $head = null
  ) {
  
    $header = array('typ' => 'JWT', 'alg' => $alg);
  
    if ($keyId !== null) {
  
      $header['kid'] = $keyId;
  
    }
  
    if ( isset($head) && is_array($head) ) {
  
      $header = array_merge($head, $header);
  
    }
  
    $segments = array();
    $segments[] = static::urlsafeB64Encode(static::jsonEncode($header));
    $segments[] = static::urlsafeB64Encode(static::jsonEncode($payload));
    $signing_input = implode('.', $segments);

    $signature = static::sign($signing_input, $key, $alg);
    $segments[] = static::urlsafeB64Encode($signature);

    return implode('.', $segments);
  
  }

  /**
  * Sign a string with a given key and algorithm.
  *
  * @param string            $msg    The message to sign
  * @param string|resource   $key    The secret key
  * @param string            $alg    The signing algorithm.
  *                                  Supported algorithms are 'HS256', 'HS384', 'HS512' and 'RS256'
  *
  * @return string An encrypted message
  *
  * @throws DomainException Unsupported algorithm was specified
  **/
  
  public static function sign($msg, $key, $alg = 'HS256') {
        
    if (empty(static::$supported_algs[$alg])) {
    
      throw new DomainException('Algorithm not supported');
    
    }
    
    list($function, $algorithm) = static::$supported_algs[$alg];
    
    switch($function) {
    
      case 'hash_hmac':
    
        return hash_hmac($algorithm, $msg, $key, true);
      
      case 'openssl':
      
        $signature = '';
        $success = openssl_sign($msg, $signature, $key, $algorithm);
        
        if (!$success) {
        
          throw new DomainException("OpenSSL unable to sign data");
        
        } else {
        
          return $signature;
        
        }
        
    }
    
  }

  /**
  * Verify a signature with the message, key and method. Not all methods
  * are symmetric, so we must have a separate verify and sign method.
  *
  * @param string            $msg        The original message (header and body)
  * @param string            $signature  The original signature
  * @param string|resource   $key        For HS*, a string key works. for RS*, must be a resource of an openssl public key
  * @param string            $alg        The algorithm
  *
  * @return bool
  *
  * @throws DomainException Invalid Algorithm or OpenSSL failure
  **/
  
  private static function verify($msg, $signature, $key, $alg) {
        
    if (empty(static::$supported_algs[$alg])) {
    
      throw new DomainException('Algorithm not supported');
    
    }

    list($function, $algorithm) = static::$supported_algs[$alg];
    
    switch($function) {
    
      case 'openssl':
    
        $success = openssl_verify($msg, $signature, $key, $algorithm);
      
        if ($success === 1) {
      
          return true;
      
        } elseif ($success === 0) {
      
          return false;
      
        }
        
        throw new DomainException(
          'OpenSSL error: ' . openssl_error_string()
        );
        
      case 'hash_hmac':
      
      default:
      
        $hash = hash_hmac($algorithm, $msg, $key, true);
        
        if (function_exists('hash_equals')) {
        
          return hash_equals($signature, $hash);
        
        }
        
        $len = min(static::safeStrlen($signature), static::safeStrlen($hash));

        $status = 0;
        
        for ($i = 0; $i < $len; $i++) {
        
          $status |= (ord($signature[$i]) ^ ord($hash[$i]));
        
        }
        
        $status |= (static::safeStrlen($signature) ^ static::safeStrlen($hash));

        return ($status === 0);
        
    }
  
  }

  /**
  * Decode a JSON string into a PHP object.
  *
  * @param string $input JSON string
  *
  * @return object Object representation of JSON string
  *
  * @throws DomainException Provided string was invalid JSON
  **/
  
  public static function jsonDecode($input){
       
    if (
      version_compare(PHP_VERSION, '5.4.0', '>=') && 
      !(defined('JSON_C_VERSION') 
      && PHP_INT_SIZE > 4)
    ) {
    
      $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
    
    } else {
    
      $max_int_length = strlen((string) PHP_INT_MAX) - 1;
      $json_without_bigints = preg_replace(
        '/:\s*(-?\d{'.$max_int_length.',})/', ': "$1"', $input);
      
      $obj = json_decode($json_without_bigints);
    
    }

    if (function_exists('json_last_error') && $errno = json_last_error()) {
    
      static::handleJsonError($errno);
    
    } elseif ($obj === null && $input !== 'null') {
    
      throw new DomainException('Null result with non-null input');
    
    }
    
    return $obj;
  
  }

  /**
  * Encode a PHP object into a JSON string.
  *
  * @param object|array $input A PHP object or array
  *
  * @return string JSON representation of the PHP object or array
  *
  * @throws DomainException Provided object could not be encoded to valid JSON
  **/
   
  public static function jsonEncode($input) {
        
    $json = json_encode($input);
    
    if (function_exists('json_last_error') && $errno = json_last_error()) {
    
      static::handleJsonError($errno);
    
    } elseif ($json === 'null' && $input !== null) {
    
      throw new DomainException('Null result with non-null input');
    
    }
    
    return $json;
    
  }

  /**
  * Decode a string with URL-safe Base64.
  *
  * @param string $input A Base64 encoded string
  *
  * @return string A decoded string
  **/
  
  public static function urlsafeB64Decode($input) {
        
    $remainder = strlen($input) % 4;
    
    if ($remainder) {
    
      $padlen = 4 - $remainder;
      $input .= str_repeat('=', $padlen);
    
    }
    
    return base64_decode(strtr($input, '-_', '+/'));
    
  }

  /**
  * Encode a string with URL-safe Base64.
  *
  * @param string $input The string you want encoded
  *
  * @return string The base64 encode of what you passed in
  **/
   
  public static function urlsafeB64Encode($input) {
        
    return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    
  }

  /**
  * Helper method to create a JSON error.
  *
  * @param int $errno An error number from json_last_error()
  *
  * @return void
  **/
   
  private static function handleJsonError($errno) {
        
    $messages = array(
    
      JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
      JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
      JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
      JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
      JSON_ERROR_UTF8 => 'Malformed UTF-8 characters' //PHP >= 5.3.3
    
    );
    
    throw new DomainException(
    
      isset($messages[$errno])
        ? $messages[$errno]
        : 'Unknown JSON error: ' . $errno
  
    );
    
  }

  /**
  * Get the number of bytes in cryptographic strings.
  *
  * @param string
  *
  * @return int
  **/
   
  private static function safeStrlen($str) {
        
    if (function_exists('mb_strlen')) {
    
      return mb_strlen($str, '8bit');
    
    }
    
    return strlen($str);
    
  }

}